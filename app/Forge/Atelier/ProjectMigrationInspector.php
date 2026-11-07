<?php

declare(strict_types=1);

namespace App\Forge\Atelier;

use App\Models\ForgeProject;
use Database\Migration\Migrator;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Lit l’état des migrations d’un projet généré (sans passer par le rendu CLI).
 */
final class ProjectMigrationInspector
{
    /**
     * @return array{
     *     ok: bool,
     *     badge: 'up_to_date'|'pending'|'orphan'|'empty'|'error',
     *     label: string,
     *     pending_count: int,
     *     applied_count: int,
     *     orphan_count: int,
     *     rows: list<array{migration: string, batch: int|null, status: string, executed_at: string|null}>,
     *     error: ?string
     * }
     */
    public function inspect(ForgeProject $project): array
    {
        if (!$project->directoryExists()) {
            return $this->errorState('Projet introuvable sur le disque.');
        }

        $migrationsPath = $project->path . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
        if (!is_dir($migrationsPath)) {
            return [
                'ok' => true,
                'badge' => 'empty',
                'label' => 'Aucune migration',
                'pending_count' => 0,
                'applied_count' => 0,
                'orphan_count' => 0,
                'rows' => [],
                'error' => null,
            ];
        }

        try {
            $pdo = $this->openProjectPdo($project->path);
            $migrator = new Migrator($pdo, $migrationsPath);
            /** @var list<array{migration: string, batch: int|null, status: string, executed_at: string|null}> $rows */
            $rows = $migrator->status();
        } catch (Throwable $e) {
            return $this->errorState($e->getMessage());
        }

        $pending = 0;
        $applied = 0;
        $orphan = 0;
        foreach ($rows as $row) {
            match ($row['status']) {
                'pending' => $pending++,
                'applied' => $applied++,
                'orphan' => $orphan++,
                default => null,
            };
        }

        if ($rows === []) {
            $badge = 'empty';
            $label = 'Aucune migration';
        } elseif ($pending > 0) {
            $badge = 'pending';
            $label = $pending === 1 ? '1 en attente' : $pending . ' en attente';
        } elseif ($orphan > 0) {
            $badge = 'orphan';
            $label = $orphan === 1 ? '1 orpheline' : $orphan . ' orphelines';
        } else {
            $badge = 'up_to_date';
            $label = 'À jour';
        }

        return [
            'ok' => true,
            'badge' => $badge,
            'label' => $label,
            'pending_count' => $pending,
            'applied_count' => $applied,
            'orphan_count' => $orphan,
            'rows' => $rows,
            'error' => null,
        ];
    }

    /**
     * @return array{
     *     ok: bool,
     *     badge: 'error',
     *     label: string,
     *     pending_count: int,
     *     applied_count: int,
     *     orphan_count: int,
     *     rows: list<array{migration: string, batch: int|null, status: string, executed_at: string|null}>,
     *     error: string
     * }
     */
    private function errorState(string $message): array
    {
        return [
            'ok' => false,
            'badge' => 'error',
            'label' => 'Erreur',
            'pending_count' => 0,
            'applied_count' => 0,
            'orphan_count' => 0,
            'rows' => [],
            'error' => $message,
        ];
    }

    private function openProjectPdo(string $projectPath): PDO
    {
        $env = $this->readProjectEnv($projectPath);
        $driver = $env['DB_DRIVER'] ?? 'sqlite';

        if ($driver !== 'sqlite') {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $env['DB_HOST'] ?? '127.0.0.1',
                (int) ($env['DB_PORT'] ?? 3306),
                $env['DB_DATABASE'] ?? '',
                $env['DB_CHARSET'] ?? 'utf8mb4',
            );
            $pdo = new PDO(
                $dsn,
                (string) ($env['DB_USERNAME'] ?? ''),
                (string) ($env['DB_PASSWORD'] ?? ''),
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $pdo;
        }

        $relative = ltrim((string) ($env['DB_DATABASE'] ?? 'database/starter.sqlite'), '/\\');
        $dbPath = $projectPath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
        $dir = dirname($dbPath);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Impossible de créer le dossier database du projet.');
        }

        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    /**
     * @return array<string, string>
     */
    private function readProjectEnv(string $projectPath): array
    {
        $envFile = $projectPath . DIRECTORY_SEPARATOR . '.env';
        if (!is_file($envFile)) {
            $envFile = $projectPath . DIRECTORY_SEPARATOR . '.env.example';
        }
        if (!is_file($envFile)) {
            return [];
        }

        $out = [];
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\"'");
            if ($key !== '') {
                $out[$key] = $value;
            }
        }

        return $out;
    }
}
