<?php

declare(strict_types=1);

namespace App\Forge\Install;

use Database\Migration\Migrator;
use PDO;
use Throwable;

/**
 * État d’installation Forge (migrations locales, pas celles des projets générés).
 */
final class InstallGate
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $migrationsPath,
    ) {
    }

    public static function forBasePath(PDO $pdo, string $basePath): self
    {
        return new self(
            $pdo,
            $basePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations',
        );
    }

    public function needsMigrate(): bool
    {
        try {
            foreach ($this->status() as $row) {
                if (($row['status'] ?? '') === 'pending') {
                    return true;
                }
            }

            return false;
        } catch (Throwable) {
            return true;
        }
    }

    /**
     * @return list<array{migration: string, batch: int|null, status: string, executed_at: string|null}>
     */
    public function status(): array
    {
        return $this->migrator()->status();
    }

    /**
     * @return list<string> Fichiers migrés
     */
    public function migrate(): array
    {
        return $this->migrator()->run();
    }

    /**
     * @return array{
     *     needs_migrate: bool,
     *     pending_count: int,
     *     applied_count: int,
     *     rows: list<array{migration: string, batch: int|null, status: string, executed_at: string|null}>,
     *     error: ?string
     * }
     */
    public function summary(): array
    {
        try {
            $rows = $this->status();
            $pending = 0;
            $applied = 0;
            foreach ($rows as $row) {
                if ($row['status'] === 'pending') {
                    $pending++;
                } elseif ($row['status'] === 'applied') {
                    $applied++;
                }
            }

            return [
                'needs_migrate' => $pending > 0,
                'pending_count' => $pending,
                'applied_count' => $applied,
                'rows' => $rows,
                'error' => null,
            ];
        } catch (Throwable $e) {
            return [
                'needs_migrate' => true,
                'pending_count' => 0,
                'applied_count' => 0,
                'rows' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    private function migrator(): Migrator
    {
        return new Migrator($this->pdo, $this->migrationsPath);
    }
}
