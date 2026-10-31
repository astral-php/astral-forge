<?php

declare(strict_types=1);

namespace App\Forge\Writer;

use App\Forge\Exception\CollisionException;
use App\Forge\Exception\GenerationException;
use App\Forge\Plan\GenerationPlan;
use App\Forge\Plan\PlanOperation;
use App\Forge\Report\GenerationReport;

/**
 * Applique un plan dans une destination déjà validée (SecurePathGuard).
 */
final class PlanExecutor
{
    public function __construct(
        private readonly ComposerCreateProjectRunner $composerRunner,
    ) {
    }

    public function execute(GenerationPlan $plan, bool $dryRun = false): GenerationReport
    {
        $entries = [];
        $errors = [];

        try {
            foreach ($plan->operations as $operation) {
                $entries[] = $this->runOperation($operation, $dryRun, $plan);
            }
        } catch (CollisionException | GenerationException $e) {
            $errors[] = $e->getMessage();

            return new GenerationReport(
                success: false,
                dryRun: $dryRun,
                destination: $plan->destination,
                entries: $entries,
                errors: $errors,
            );
        }

        return new GenerationReport(
            success: true,
            dryRun: $dryRun,
            destination: $plan->destination,
            entries: $entries,
            errors: [],
        );
    }

    /**
     * @return array{operation: string, path: string, status: string, message: string}
     */
    private function runOperation(PlanOperation $operation, bool $dryRun, GenerationPlan $plan): array
    {
        return match ($operation->type) {
            PlanOperation::TYPE_ENSURE_EMPTY_DIR => $this->ensureEmptyDir($operation, $dryRun),
            PlanOperation::TYPE_COMPOSER_CREATE_PROJECT => $this->createProject($operation, $dryRun),
            PlanOperation::TYPE_NOTE => [
                'operation' => $operation->type,
                'path' => $operation->path,
                'status' => 'ok',
                'message' => $operation->description !== '' ? $operation->description : 'note',
            ],
            default => throw new GenerationException('Type d’opération inconnu : ' . $operation->type),
        };
    }

    /**
     * @return array{operation: string, path: string, status: string, message: string}
     */
    private function ensureEmptyDir(PlanOperation $operation, bool $dryRun): array
    {
        $path = $operation->path;

        if (is_dir($path)) {
            $items = array_values(array_filter(
                scandir($path) ?: [],
                static fn (string $item): bool => $item !== '.' && $item !== '..'
            ));
            if ($items !== []) {
                throw new CollisionException(
                    'Destination non vide : ' . $path . ' — greenfield uniquement (0.1).'
                );
            }
        } elseif (file_exists($path)) {
            throw new CollisionException('La destination existe déjà comme fichier : ' . $path);
        }

        if ($dryRun) {
            return [
                'operation' => $operation->type,
                'path' => $path,
                'status' => 'planned',
                'message' => $operation->description,
            ];
        }

        return [
            'operation' => $operation->type,
            'path' => $path,
            'status' => 'ok',
            'message' => 'Destination libre.',
        ];
    }

    /**
     * @return array{operation: string, path: string, status: string, message: string}
     */
    private function createProject(PlanOperation $operation, bool $dryRun): array
    {
        $package = (string) ($operation->meta['package'] ?? '');
        if ($package === '') {
            throw new GenerationException('Package Composer manquant pour create-project.');
        }

        if ($dryRun) {
            return [
                'operation' => $operation->type,
                'path' => $operation->path,
                'status' => 'planned',
                'message' => $operation->description,
            ];
        }

        $this->composerRunner->run($package, $operation->path);

        $projectName = (string) ($operation->meta['project_name'] ?? '');
        if ($projectName !== '') {
            $this->patchGeneratedEnv($operation->path, $projectName);
        }

        return [
            'operation' => $operation->type,
            'path' => $operation->path,
            'status' => 'ok',
            'message' => 'Projet créé via Composer.',
        ];
    }

    private function patchGeneratedEnv(string $projectDir, string $projectName): void
    {
        $envExample = $projectDir . DIRECTORY_SEPARATOR . '.env.example';
        $env = $projectDir . DIRECTORY_SEPARATOR . '.env';
        $display = $this->humanizeName($projectName);

        foreach ([$envExample, $env] as $file) {
            if (!is_file($file)) {
                continue;
            }
            $contents = file_get_contents($file);
            if ($contents === false) {
                continue;
            }
            $contents = preg_replace(
                '/^APP_NAME=.*$/m',
                'APP_NAME="' . $display . '"',
                $contents
            ) ?? $contents;
            file_put_contents($file, $contents);
        }
    }

    private function humanizeName(string $kebab): string
    {
        $parts = explode('-', $kebab);
        $parts = array_map(
            static fn (string $p): string => $p === '' ? '' : strtoupper($p[0]) . substr($p, 1),
            $parts
        );

        return implode(' ', $parts);
    }
}
