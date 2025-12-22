<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class SyncVpsAllTables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:vps-all-tables 
                            {--full : Perform full sync (ignore last sync tracker)}
                            {--table= : Sync specific table only (punch_logs, manual_attendances, whatsapp_logs, notification_queue)}
                            {--skip-existing : Skip records that already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all related tables from VPS database to local (punch_logs, manual_attendances, whatsapp_logs, notification_queue)';

    /**
     * Tables to sync with their configurations
     */
    private array $tablesConfig = [
        // Core data tables
        'students' => [
            'unique_key' => ['roll_number'],
            'date_column' => null,
            'time_column' => null,
            'update_existing' => true, // Update existing records
        ],
        'employees' => [
            'unique_key' => ['roll_number'],
            'date_column' => null,
            'time_column' => null,
            'update_existing' => true, // Update existing records
        ],
        'courses' => [
            'unique_key' => ['name'],
            'date_column' => null,
            'time_column' => null,
            'update_existing' => true,
        ],
        'batches' => [
            'unique_key' => ['name'],
            'date_column' => null,
            'time_column' => null,
            'update_existing' => true,
        ],
        // Attendance and logs
        'punch_logs' => [
            'unique_key' => ['employee_id', 'punch_date', 'punch_time'],
            'date_column' => 'punch_date',
            'time_column' => 'punch_time',
            'update_existing' => false,
        ],
        'manual_attendances' => [
            'unique_key' => ['roll_number', 'punch_date', 'punch_time', 'state'],
            'date_column' => 'punch_date',
            'time_column' => 'punch_time',
            'update_existing' => false,
        ],
        'whatsapp_logs' => [
            'unique_key' => ['roll_number', 'punch_date', 'punch_time', 'state'],
            'date_column' => 'punch_date',
            'time_column' => 'punch_time',
            'update_existing' => false,
        ],
        'notification_queue' => [
            'unique_key' => ['roll_number', 'punch_date', 'punch_time'],
            'date_column' => null,
            'time_column' => null,
            'update_existing' => false,
        ],
        // Settings (optional - be careful with this)
        'settings' => [
            'unique_key' => ['key'],
            'date_column' => null,
            'time_column' => null,
            'update_existing' => true,
        ],
    ];

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

        // Ensure sync tracker table exists
        $this->ensureSyncTrackerTable();

        try {
            $vpsConnection = DB::connection('vps');
            $tableToSync = $this->option('table');
            $isFullSync = $this->option('full');
            $skipExisting = $this->option('skip-existing');

            // Determine which tables to sync
            $tablesToSync = $tableToSync 
                ? [$tableToSync => $this->tablesConfig[$tableToSync] ?? null]
                : $this->tablesConfig;

            if ($tableToSync && !isset($this->tablesConfig[$tableToSync])) {
                $this->error("Unknown table: {$tableToSync}");
                $this->info("Available tables: " . implode(', ', array_keys($this->tablesConfig)));
                return Command::FAILURE;
            }

            $this->info('═══════════════════════════════════════════════════════════');
            $this->info('  VPS DATABASE FULL SYNC - All Tables');
            $this->info('═══════════════════════════════════════════════════════════');
            $this->info('');
            $this->info('This will sync:');
            $this->info('  • Students (name, father_name, class, batch, phone numbers)');
            $this->info('  • Employees (name, father_name, mobile, category)');
            $this->info('  • Courses (classes)');
            $this->info('  • Batches');
            $this->info('  • Punch Logs (machine attendance)');
            $this->info('  • Manual Attendances (manual login/logout)');
            $this->info('  • WhatsApp Logs (message status)');
            $this->info('  • Notification Queue');
            $this->info('  • Settings');
            $this->newLine();

            $totalSyncedAll = 0;

            foreach ($tablesToSync as $tableName => $config) {
                if (!$config) {
                    continue;
                }

                $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
                $this->info("Syncing table: {$tableName}");
                $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

                // Check if table exists on VPS
                if (!$this->tableExists($vpsConnection, $tableName)) {
                    $this->warn("Table '{$tableName}' does not exist on VPS. Skipping...");
                    $this->newLine();
                    continue;
                }

                // Check if table exists locally
                if (!Schema::hasTable($tableName)) {
                    $this->warn("Local table '{$tableName}' does not exist. Skipping...");
                    $this->newLine();
                    continue;
                }

                $synced = $this->syncTable($vpsConnection, $tableName, $config, $isFullSync, $skipExisting);
                $totalSyncedAll += $synced;

                $this->newLine();
            }

            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info("✓ All tables sync completed!");
            $this->info("  Total records synced across all tables: {$totalSyncedAll}");
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * Sync a single table
     */
    private function syncTable($vpsConnection, string $tableName, array $config, bool $isFullSync, bool $skipExisting): int
    {
        $lastSync = $this->getLastSyncInfo($tableName);
        $hasIdColumn = $this->tableHasColumn($vpsConnection, $tableName, 'id');
        
        // Get total records on VPS
        $totalVpsRecords = $vpsConnection->table($tableName)->count();
        $this->info("Total records in VPS: {$totalVpsRecords}");

        // Build query
        $query = $vpsConnection->table($tableName);

        if ($isFullSync || !$lastSync) {
            $this->info("Performing FULL sync (all records)...");
            // Always add orderBy for chunk() to work
            if ($hasIdColumn) {
                $query->orderBy('id', 'asc');
            } elseif ($config['date_column']) {
                $query->orderBy($config['date_column'], 'asc');
                if ($config['time_column']) {
                    $query->orderBy($config['time_column'], 'asc');
                }
            } elseif (in_array('roll_number', $this->getVpsTableColumns($vpsConnection, $tableName))) {
                // For students/employees, order by roll_number
                $query->orderBy('roll_number', 'asc');
            } elseif (in_array('name', $this->getVpsTableColumns($vpsConnection, $tableName))) {
                // For courses/batches, order by name
                $query->orderBy('name', 'asc');
            } else {
                // Fallback: order by first available column
                $vpsColumns = $this->getVpsTableColumns($vpsConnection, $tableName);
                if (!empty($vpsColumns)) {
                    $query->orderBy($vpsColumns[0], 'asc');
                } else {
                    $query->orderByRaw('1'); // Last resort
                }
            }
        } else {
            if ($hasIdColumn && isset($lastSync['last_synced_id'])) {
                $this->info("Performing INCREMENTAL sync from ID: {$lastSync['last_synced_id']}...");
                $query->where('id', '>', $lastSync['last_synced_id'])->orderBy('id', 'asc');
            } elseif ($config['date_column'] && isset($lastSync['last_synced_at'])) {
                $lastSyncDate = Carbon::parse($lastSync['last_synced_at'])->format('Y-m-d');
                $this->info("Performing INCREMENTAL sync from date: {$lastSyncDate}...");
                $query->where($config['date_column'], '>=', $lastSyncDate)
                      ->orderBy($config['date_column'], 'asc');
                if ($config['time_column']) {
                    $query->orderBy($config['time_column'], 'asc');
                }
            } else {
                $this->info("Performing FULL sync (no previous sync found)...");
                // Add orderBy for chunk()
                if ($hasIdColumn) {
                    $query->orderBy('id', 'asc');
                } elseif ($config['date_column']) {
                    $query->orderBy($config['date_column'], 'asc');
                    if ($config['time_column']) {
                        $query->orderBy($config['time_column'], 'asc');
                    }
                }
            }
        }

        // Get local table columns
        $localColumns = $this->getLocalTableColumns($tableName);
        if (empty($localColumns)) {
            $this->error("Could not get local table columns for {$tableName}");
            return 0;
        }

        // Get VPS table columns
        $vpsColumns = $this->getVpsTableColumns($vpsConnection, $tableName);
        if (empty($vpsColumns)) {
            $this->error("Could not get VPS table columns for {$tableName}");
            return 0;
        }

        // Find common columns
        $commonColumns = array_intersect($localColumns, $vpsColumns);
        $this->info("Syncing " . count($commonColumns) . " common columns");

        // Count records to sync
        $recordsToSync = (clone $query)->count();
        $this->info("Records to sync: {$recordsToSync}");

        if ($recordsToSync == 0) {
            $this->info("No new records to sync.");
            return 0;
        }

        // Process in batches
        $batchSize = 1000;
        $totalSynced = 0;
        $lastId = $lastSync['last_synced_id'] ?? 0;
        $lastSyncDateTime = null;
        $bar = $this->output->createProgressBar($recordsToSync);
        $bar->start();

        $query->chunk($batchSize, function ($records) use (
            &$totalSynced, 
            &$lastId, 
            &$lastSyncDateTime, 
            $bar, 
            $hasIdColumn, 
            $tableName, 
            $config, 
            $commonColumns,
            $skipExisting,
            $vpsConnection
        ) {
            foreach ($records as $record) {
                $recordArray = (array) $record;

                // Check if record already exists
                if ($skipExisting || !$this->recordExists($tableName, $recordArray, $config['unique_key'])) {
                    // Filter to only common columns
                    $filteredRecord = [];
                    foreach ($commonColumns as $column) {
                        if (isset($recordArray[$column])) {
                            $filteredRecord[$column] = $recordArray[$column];
                        }
                    }

                    // Skip if required unique key columns are missing
                    $hasRequiredColumns = true;
                    foreach ($config['unique_key'] as $key) {
                        if (!isset($filteredRecord[$key])) {
                            $hasRequiredColumns = false;
                            break;
                        }
                    }

                    if ($hasRequiredColumns) {
                        try {
                            // Check if we should update existing records
                            $shouldUpdate = $config['update_existing'] ?? false;
                            
                            if ($shouldUpdate) {
                                // Use updateOrInsert to update existing or insert new
                                $uniqueConditions = [];
                                $updateData = [];
                                
                                foreach ($filteredRecord as $key => $value) {
                                    if (in_array($key, $config['unique_key'])) {
                                        // This is a unique key - use in conditions
                                        $uniqueConditions[$key] = $value;
                                    } else {
                                        // This is regular data - use in update
                                        $updateData[$key] = $value;
                                    }
                                }
                                
                                // Always update updated_at if it exists
                                if (in_array('updated_at', $commonColumns)) {
                                    $updateData['updated_at'] = now();
                                }
                                // Don't update created_at if record exists
                                unset($updateData['created_at']);
                                
                                DB::table($tableName)->updateOrInsert($uniqueConditions, $updateData);
                                $totalSynced++;
                            } else {
                                // Use insertOrIgnore to handle duplicates gracefully
                                DB::table($tableName)->insertOrIgnore($filteredRecord);
                                $totalSynced++;
                            }

                            // Track last ID
                            if ($hasIdColumn && isset($recordArray['id'])) {
                                $lastId = max($lastId, (int) $recordArray['id']);
                            }

                            // Track last sync datetime
                            if ($config['date_column'] && isset($recordArray[$config['date_column']])) {
                                $dateValue = $recordArray[$config['date_column']];
                                if ($config['time_column'] && isset($recordArray[$config['time_column']])) {
                                    $timeValue = $recordArray[$config['time_column']];
                                    $recordDateTime = Carbon::parse($dateValue . ' ' . $timeValue);
                                } else {
                                    $recordDateTime = Carbon::parse($dateValue);
                                }
                                if (!$lastSyncDateTime || $recordDateTime->gt($lastSyncDateTime)) {
                                    $lastSyncDateTime = $recordDateTime;
                                }
                            }
                        } catch (\Exception $e) {
                            // Silently skip errors (duplicates, etc.)
                        }
                    }
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();

        // Update sync tracker
        $this->updateSyncTracker($tableName, $lastId, $totalSynced, $lastSyncDateTime, $hasIdColumn);

        $this->info("✓ {$tableName}: {$totalSynced} records synced");

        return $totalSynced;
    }

    /**
     * Check if record exists
     */
    private function recordExists(string $tableName, array $record, array $uniqueKey): bool
    {
        $query = DB::table($tableName);
        foreach ($uniqueKey as $key) {
            if (!isset($record[$key])) {
                return false;
            }
            $query->where($key, $record[$key]);
        }
        return $query->exists();
    }

    /**
     * Get local table columns
     */
    private function getLocalTableColumns(string $tableName): array
    {
        try {
            $columns = DB::select("SHOW COLUMNS FROM `{$tableName}`");
            return array_column($columns, 'Field');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get VPS table columns
     */
    private function getVpsTableColumns($connection, string $tableName): array
    {
        try {
            $columns = $connection->select("SHOW COLUMNS FROM `{$tableName}`");
            return array_column($columns, 'Field');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check if table exists
     */
    private function tableExists($connection, string $tableName): bool
    {
        try {
            $tables = $connection->select("SHOW TABLES LIKE '{$tableName}'");
            return !empty($tables);
        } catch (\Exception $e) {
            return false;
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
                $table->string('table_name')->unique();
                $table->bigInteger('last_synced_id')->default(0);
                $table->timestamp('last_synced_at')->nullable();
                $table->integer('total_records_synced')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Get last sync information
     */
    private function getLastSyncInfo(string $tableName): ?array
    {
        $tracker = DB::table('vps_sync_tracker')
            ->where('table_name', $tableName)
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
    private function updateSyncTracker(string $tableName, ?int $lastId, int $syncedCount, ?Carbon $lastSyncDateTime, bool $hasIdColumn): void
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
            ['table_name' => $tableName],
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
