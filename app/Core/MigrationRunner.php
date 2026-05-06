<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class MigrationRunner
{
    private Database $database;

    private string $migrationsPath;

    public function __construct(Database $database, string $migrationsPath)
    {
        $this->database = $database;
        $this->migrationsPath = rtrim($migrationsPath, DIRECTORY_SEPARATOR);
    }

    /**
     * @return array<int, string>
     */
    public function runPending(): array
    {
        $this->ensureMigrationsTable();

        $executed = $this->executedMigrations();
        $ran = [];

        foreach ($this->migrationFiles() as $file) {
            $migrationName = basename($file);

            if (in_array($migrationName, $executed, true)) {
                continue;
            }

            $migration = require $file;

            if (!is_object($migration) || !method_exists($migration, 'up') || !method_exists($migration, 'down')) {
                throw new \RuntimeException('Invalid migration: ' . $migrationName);
            }

            $pdo = $this->database->connection();
            $pdo->beginTransaction();

            try {
                $migration->up($pdo);
                $this->database->query(
                    'INSERT INTO migrations (migration, executed_at) VALUES (:migration, NOW())',
                    ['migration' => $migrationName]
                );
                if ($pdo->inTransaction()) {
                    $pdo->commit();
                }
            } catch (\Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $exception;
            }

            $ran[] = $migrationName;
        }

        return $ran;
    }

    private function ensureMigrationsTable(): void
    {
        $this->database->execute(
            'CREATE TABLE IF NOT EXISTS migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                executed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /**
     * @return array<int, string>
     */
    private function executedMigrations(): array
    {
        return array_map(
            static fn (array $row): string => (string) $row['migration'],
            $this->database->fetchAll('SELECT migration FROM migrations ORDER BY migration ASC')
        );
    }

    /**
     * @return array<int, string>
     */
    private function migrationFiles(): array
    {
        $files = glob($this->migrationsPath . DIRECTORY_SEPARATOR . '*.php') ?: [];
        sort($files);

        return array_values($files);
    }
}
