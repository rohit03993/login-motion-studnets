<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class SyncVpsPunchLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:vps-punch-logs {--full : Perform full sync (ignore last sync tracker)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync punch_logs data from VPS database to local database (for local testing only)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Check if VPS connection is configured
        if (!config('database.connections.vps')) {
            $this->error('VPS database connection not configured. Check your .env file.');
            return Command::FAILURE;
        }

        // Check if local punch_logs table exists
        if (!Schema::hasTable('punch_logs')) {
            $this->error('Local punch_logs table does not exist. Please create it first.');
            return Command::FAILURE;
        }

        // Ensure sync tracker table exists
        $this->ensureSyncTrackerTable();

        try {
            // Connect to VPS database
            $vpsConnection = DB::connection('vps');
            
            // Get last sync info
            $lastSync = $this->getLastSyncInfo();
            $isFullSync = $this->option('full') || !$lastSync;

            // Check if table has 'id' column
            $hasIdColumn = $this->tableHasColumn($vpsConnection, 'punch_logs', 'id');
            
            if ($isFullSync) {
                $this->info('Performing FULL sync (all records - no date filter)...');
                $minDate = null; // No date filter for full sync - sync ALL records
            } else {
                if ($hasIdColumn && isset($lastSync['last_synced_id'])) {
                    $this->info("Performing INCREMENTAL sync from ID: {$lastSync['last_synced_id']}...");
                } else {
                    $lastDate = $lastSync['last_synced_at'] ?? '2025-12-15';
                    $this->info("Performing INCREMENTAL sync from date: {$lastDate}...");
                }
            }

            // Determine starting point
            $query = $vpsConnection->table('punch_logs');
            
            if ($hasIdColumn && !$isFullSync && isset($lastSync['last_synced_id'])) {
                // Use ID-based incremental sync if ID column exists
                $query->where('id', '>', $lastSync['last_synced_id'])
                      ->orderBy('id', 'asc');
            } else {
                // Use date-based sync (works for tables without ID column)
                if (!$isFullSync && isset($lastSync['last_synced_at'])) {
                    // Sync records newer than last sync time
                    $lastSyncDate = Carbon::parse($lastSync['last_synced_at'])->format('Y-m-d');
                    $query->where('punch_date', '>=', $lastSyncDate);
                } else {
                    // For full sync, sync ALL records (no date filter)
                    // Only apply date filter if minDate is explicitly set
                    if ($minDate) {
                        $query->where('punch_date', '>=', $minDate);
                    }
                    // Otherwise, no date filter - sync everything
                }
                // Order by date and time for consistent sync
                $query->orderBy('punch_date', 'asc')
                      ->orderBy('punch_time', 'asc');
            }

            // Process in batches to avoid memory issues
            $batchSize = 1000;
            $totalSynced = 0;
            $lastId = $lastSync['last_synced_id'] ?? 0;
            $lastSyncDateTime = null;
            $bar = $this->output->createProgressBar();
            $bar->start();

            // Debug: Check total records in VPS table (before filtering)
            $totalVpsRecords = $vpsConnection->table('punch_logs')->count();
            $this->info("Total records in VPS punch_logs table: {$totalVpsRecords}");
            
            // Get column names from VPS table (to handle any column structure)
            $firstRecord = $vpsConnection->table('punch_logs')->first();
            if (!$firstRecord) {
                $this->warn('No records found in VPS punch_logs table.');
                return Command::SUCCESS;
            }
            
            // Debug: Show first record structure
            $recordKeys = array_keys((array)$firstRecord);
            $this->info("Sample record columns: " . implode(', ', $recordKeys));
            
            // Debug: Show sample record data
            $sampleData = [];
            foreach ($recordKeys as $key) {
                $sampleData[] = "{$key}=" . (is_string($firstRecord->$key) ? substr($firstRecord->$key, 0, 20) : $firstRecord->$key);
            }
            $this->info("Sample record: " . implode(', ', $sampleData));
            
            // Debug: Check how many records match the query (build query copy for counting)
            $countQuery = $vpsConnection->table('punch_logs');
            if ($hasIdColumn && !$isFullSync && isset($lastSync['last_synced_id'])) {
                $countQuery->where('id', '>', $lastSync['last_synced_id']);
            } else {
                if (!$isFullSync && isset($lastSync['last_synced_at'])) {
                    $lastSyncDate = Carbon::parse($lastSync['last_synced_at'])->format('Y-m-d');
                    $hasPunchDateCol = $this->tableHasColumn($vpsConnection, 'punch_logs', 'punch_date');
                    if ($hasPunchDateCol) {
                        $countQuery->where('punch_date', '>=', $lastSyncDate);
                    }
                } elseif ($isFullSync) {
                    $hasPunchDateCol = $this->tableHasColumn($vpsConnection, 'punch_logs', 'punch_date');
                    if ($hasPunchDateCol && isset($minDate)) {
                        $countQuery->where('punch_date', '>=', $minDate);
                    }
                }
            }
            $queryCount = $countQuery->count();
            $this->info("Records matching query criteria: {$queryCount}");
            
            if ($queryCount == 0 && $totalVpsRecords > 0) {
                $this->warn("No records match the query criteria, but {$totalVpsRecords} total records exist.");
                if ($isFullSync && isset($minDate)) {
                    $this->warn("Date filter might be excluding records. Min date filter: {$minDate}");
                    $this->info("Checking records without date filter...");
                    $noFilterCount = $vpsConnection->table('punch_logs')->count();
                    $this->info("Total records without date filter: {$noFilterCount}");
                }
            }
            
            // Debug: Check local records count
            $localCount = DB::table('punch_logs')->count();
            $this->info("Current local punch_logs records: {$localCount}");
            
            // Get local table columns once (for use in chunk callback)
            $localColumnsForInsert = [];
            try {
                $localColumns = DB::select("SHOW COLUMNS FROM punch_logs");
                $localColumnsForInsert = array_column($localColumns, 'Field');
                $this->info("Local table columns: " . implode(', ', $localColumnsForInsert));
            } catch (\Exception $e) {
                $this->error("Could not check local table structure: " . $e->getMessage());
                return Command::FAILURE;
            }

            $processedInChunk = 0;
            $insertedInChunk = 0;
            $skippedInChunk = 0;
            
            $this->info("About to start chunking. Query will fetch records...");
            
            // Test: Fetch first few records directly to verify query works (use separate query)
            $testQuery = $vpsConnection->table('punch_logs');
            if ($hasIdColumn && !$isFullSync && isset($lastSync['last_synced_id'])) {
                $testQuery->where('id', '>', $lastSync['last_synced_id'])->orderBy('id', 'asc');
            } else {
                if (!$isFullSync && isset($lastSync['last_synced_at'])) {
                    $lastSyncDate = Carbon::parse($lastSync['last_synced_at'])->format('Y-m-d');
                    $testQuery->where('punch_date', '>=', $lastSyncDate)
                              ->orderBy('punch_date', 'asc')
                              ->orderBy('punch_time', 'asc');
                } elseif ($isFullSync) {
                    if ($minDate) {
                        $testQuery->where('punch_date', '>=', $minDate);
                    }
                    $testQuery->orderBy('punch_date', 'asc')->orderBy('punch_time', 'asc');
                }
            }
            $testRecords = $testQuery->limit(3)->get();
            $this->info("Test: Fetched " . $testRecords->count() . " test records directly");
            if ($testRecords->count() > 0) {
                $testRecord = $testRecords->first();
                $this->info("Test record: employee_id=" . ($testRecord->employee_id ?? 'N/A') . ", date=" . ($testRecord->punch_date ?? 'N/A'));
            }
            
            // Set progress bar total based on query count
            $bar->setMaxSteps($queryCount);
            
            $command = $this; // Capture command instance for use in closure
            
            // Rebuild query for chunking to ensure it's fresh
            $chunkQuery = $vpsConnection->table('punch_logs');
            if ($hasIdColumn && !$isFullSync && isset($lastSync['last_synced_id'])) {
                $chunkQuery->where('id', '>', $lastSync['last_synced_id'])->orderBy('id', 'asc');
            } else {
                if (!$isFullSync && isset($lastSync['last_synced_at'])) {
                    $lastSyncDate = Carbon::parse($lastSync['last_synced_at'])->format('Y-m-d');
                    $chunkQuery->where('punch_date', '>=', $lastSyncDate)
                               ->orderBy('punch_date', 'asc')
                               ->orderBy('punch_time', 'asc');
                } elseif ($isFullSync) {
                    if ($minDate) {
                        $chunkQuery->where('punch_date', '>=', $minDate);
                    }
                    $chunkQuery->orderBy('punch_date', 'asc')->orderBy('punch_time', 'asc');
                }
            }
            
            $chunkQuery->chunk($batchSize, function ($records) use (&$totalSynced, &$lastId, &$lastSyncDateTime, $bar, $hasIdColumn, &$processedInChunk, &$insertedInChunk, &$skippedInChunk, $command, $localColumnsForInsert) {
                $chunkCount = count($records);
                $command->line(""); // New line for visibility
                $command->info("✓ Processing chunk of {$chunkCount} records...");
                
                foreach ($records as $index => $record) {
                    $processedInChunk++;
                    try {
                        // Convert record to array, handling all columns dynamically
                        $recordArray = (array) $record;
                        
                        // Check if record already exists (by unique combination)
                        // Use employee_id + punch_date + punch_time as unique identifier
                        $exists = false;
                        if (isset($recordArray['employee_id']) && isset($recordArray['punch_date']) && isset($recordArray['punch_time'])) {
                            $exists = DB::table('punch_logs')
                                ->where('employee_id', (string)$recordArray['employee_id'])
                                ->where('punch_date', $recordArray['punch_date'])
                                ->where('punch_time', $recordArray['punch_time'])
                                ->exists();
                        }

                        if (!$exists) {
                            // Filter record array to only include columns that exist in BOTH VPS and local tables
                            $filteredRecord = [];
                            foreach ($recordArray as $key => $value) {
                                // Only include columns that exist in local table
                                if (in_array($key, $localColumnsForInsert)) {
                                    $filteredRecord[$key] = $value;
                                }
                            }
                            
                            // Verify we have the minimum required columns
                            if (!isset($filteredRecord['employee_id']) || !isset($filteredRecord['punch_date']) || !isset($filteredRecord['punch_time'])) {
                                $command->warn("Skipped record - missing required columns");
                                $skippedInChunk++;
                                continue;
                            }
                            
                            try {
                                // Insert into local database - copy VPS data as-is (only matching columns)
                                DB::table('punch_logs')->insert($filteredRecord);
                                $insertedInChunk++;
                                $totalSynced++;
                                
                                if ($insertedInChunk <= 5) {
                                    $command->info("✓ Inserted: employee_id={$filteredRecord['employee_id']}, date={$filteredRecord['punch_date']}, time={$filteredRecord['punch_time']}");
                                }
                            } catch (\Exception $insertError) {
                                $command->error("Insert failed: " . $insertError->getMessage());
                                $command->error("Columns tried: " . implode(', ', array_keys($filteredRecord)));
                                $skippedInChunk++;
                            }
                        } else {
                            $skippedInChunk++;
                        }

                        // Track last ID if column exists
                        if ($hasIdColumn && isset($recordArray['id'])) {
                            $lastId = max($lastId, (int) $recordArray['id']);
                        }
                        
                        // Track last sync datetime for date-based tracking
                        if (isset($recordArray['punch_date']) && isset($recordArray['punch_time'])) {
                            $recordDateTime = Carbon::parse($recordArray['punch_date'] . ' ' . $recordArray['punch_time']);
                            if (!$lastSyncDateTime || $recordDateTime->gt($lastSyncDateTime)) {
                                $lastSyncDateTime = $recordDateTime;
                            }
                        }
                        
                        // Only increment totalSynced if we actually inserted
                        // (don't count skipped duplicates)
                        $bar->advance();
                    } catch (\Exception $e) {
                        // Log all errors (not just in verbose mode for debugging)
                        $command->error("Error inserting record: " . $e->getMessage());
                        $command->error("Record data: " . json_encode(array_slice($recordArray, 0, 5))); // Show first 5 fields
                        $skippedInChunk++;
                        continue;
                    }
                }
                
                $command->info("Chunk summary: Processed={$processedInChunk}, Inserted={$insertedInChunk}, Skipped={$skippedInChunk}");
            });

            $bar->finish();
            $this->newLine();
            
            $this->info("Final summary: Processed={$processedInChunk}, Inserted={$insertedInChunk}, Skipped={$skippedInChunk}");

            // Update sync tracker
            $this->updateSyncTracker($lastId, $totalSynced, $lastSyncDateTime, $hasIdColumn);

            $this->info("✓ Sync completed successfully!");
            $this->info("  - Records synced: {$totalSynced}");
            if ($hasIdColumn && $lastId) {
                $this->info("  - Last synced ID: {$lastId}");
                $this->info("  - Next sync will continue from ID: {$lastId}");
            } else {
                $lastDateStr = $lastSyncDateTime ? $lastSyncDateTime->format('Y-m-d H:i:s') : 'N/A';
                $this->info("  - Last synced datetime: {$lastDateStr}");
                $this->info("  - Next sync will continue from this datetime");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * Ensure sync tracker table exists
     */
    private function ensureSyncTrackerTable(): void
    {
        if (!Schema::hasTable('vps_sync_tracker')) {
            Schema::create('vps_sync_tracker', function ($table) {
                $table->id();
                $table->string('table_name')->unique()->default('punch_logs');
                $table->bigInteger('last_synced_id')->default(0);
                $table->timestamp('last_synced_at')->nullable();
                $table->integer('total_records_synced')->default(0);
                $table->timestamps();
            });

            $this->info('Created sync tracker table.');
        }
    }

    /**
     * Get last sync information
     */
    private function getLastSyncInfo(): ?array
    {
        $tracker = DB::table('vps_sync_tracker')
            ->where('table_name', 'punch_logs')
            ->first();

        if (!$tracker) {
            return null;
        }

        return [
            'last_synced_id' => $tracker->last_synced_id,
            'last_synced_at' => $tracker->last_synced_at,
            'total_records_synced' => $tracker->total_records_synced,
        ];
    }

    /**
     * Update sync tracker
     */
    private function updateSyncTracker(?int $lastId, int $syncedCount, ?Carbon $lastSyncDateTime, bool $hasIdColumn): void
    {
        $updateData = [
            'last_synced_at' => $lastSyncDateTime ? $lastSyncDateTime->toDateTimeString() : now(),
            'total_records_synced' => DB::raw("total_records_synced + {$syncedCount}"),
            'updated_at' => now(),
        ];
        
        if ($hasIdColumn && $lastId) {
            $updateData['last_synced_id'] = $lastId;
        }
        
        DB::table('vps_sync_tracker')->updateOrInsert(
            ['table_name' => 'punch_logs'],
            $updateData
        );
    }

    /**
     * Check if a table has a specific column
     */
    private function tableHasColumn($connection, string $table, string $column): bool
    {
        try {
            $columns = $connection->select("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
            return !empty($columns);
        } catch (\Exception $e) {
            return false;
        }
    }
}
