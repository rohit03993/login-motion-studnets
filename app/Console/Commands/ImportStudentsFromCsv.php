<?php

namespace App\Console\Commands;

use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportStudentsFromCsv extends Command
{
    protected $signature = 'students:import {file=storage/app/imports/Download Data - motion unidesk.csv : Path to CSV file}';
    protected $description = 'Import/update students from CSV (roll, name, father, class, batch)';

    public function handle(): int
    {
        $path = $this->argument('file');
        if (!file_exists($path)) {
            $this->error("File not found: {$path}");
            return Command::FAILURE;
        }

        $rows = array_map('str_getcsv', file($path));
        if (count($rows) < 1) {
            $this->error('CSV seems empty.');
            return Command::FAILURE;
        }

        // Pick the first row that contains roll/name headers (file may start with a title line)
        $headerRowIndex = 0;
        $header = $this->normalizeHeader($rows[0]);
        if (!$this->hasRequiredHeader($header) && isset($rows[1])) {
            $headerRowIndex = 1;
            $header = $this->normalizeHeader($rows[1]);
        }

        $colMap = $this->resolveColumnMap($header);
        if (!$colMap['roll'] || !$colMap['name']) {
            $this->error('CSV must have roll and name columns (e.g., "ROLL NO", "NAME").');
            return Command::FAILURE;
        }

        $imported = 0;
        $skipped = 0;

        // Data rows start after header row
        for ($r = $headerRowIndex + 1; $r < count($rows); $r++) {
            $row = $rows[$r];
            if (!is_array($row) || count($row) === 0) {
                continue;
            }

            $roll = $row[$colMap['roll']] ?? null;
            $name = $row[$colMap['name']] ?? null;
            if (!$roll || !$name) {
                $skipped++;
                continue;
            }

            $father = $colMap['father'] ? ($row[$colMap['father']] ?? null) : null;
            $class = $colMap['class'] ? ($row[$colMap['class']] ?? null) : null;
            $batch = $colMap['batch'] ? ($row[$colMap['batch']] ?? null) : null;

            Student::updateOrCreate(
                ['roll_number' => trim($roll)],
                [
                    'name' => trim($name),
                    'father_name' => $father ? trim($father) : null,
                    'class_course' => $class ? trim($class) : null,
                    'batch' => $batch ? trim($batch) : null,
                ]
            );

            $imported++;
        }

        $this->info("Imported/updated: {$imported}, skipped (missing roll/name): {$skipped}");
        return Command::SUCCESS;
    }

    private function normalizeHeader(array $row): array
    {
        return array_map(function ($col) {
            return strtoupper(trim((string) $col));
        }, $row);
    }

    private function hasRequiredHeader(array $header): bool
    {
        $hasRoll = $this->findIndex($header, ['ROLL NO', 'ROLL', 'ROLL NO.', 'ROLL_NUMBER', 'ROLL NUMBER']) !== null;
        $hasName = $this->findIndex($header, ['NAME', 'STUDENT NAME', 'STUDENT']) !== null;
        return $hasRoll && $hasName;
    }

    private function resolveColumnMap(array $header): array
    {
        return [
            'roll' => $this->findIndex($header, ['ROLL NO', 'ROLL', 'ROLL NO.', 'ROLL_NUMBER', 'ROLL NUMBER']),
            'name' => $this->findIndex($header, ['NAME', 'STUDENT NAME', 'STUDENT']),
            'father' => $this->findIndex($header, ['FATHER', 'FATHERS NAME', 'FATHER NAME']),
            'class' => $this->findIndex($header, ['CLASS', 'CLASS COURSE', 'COURSE']),
            'batch' => $this->findIndex($header, ['BATCH']),
        ];
    }

    private function findIndex(array $header, array $candidates): ?int
    {
        foreach ($candidates as $candidate) {
            $idx = array_search($candidate, $header, true);
            if ($idx !== false) {
                return $idx;
            }
        }
        return null;
    }
}

