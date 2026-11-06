<?php

declare(strict_types=1);

namespace App\Forge\Job;

use App\Forge\Exception\GenerationException;
use JsonException;

final class GenerationJobStore
{
    public function __construct(
        private readonly string $directory = '',
    ) {
    }

    public function directory(): string
    {
        $dir = $this->directory !== ''
            ? $this->directory
            : BASE_PATH . '/storage/forge-jobs';

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new GenerationException('Impossible de créer storage/forge-jobs.');
        }

        return $dir;
    }

    /**
     * @param array<string, mixed> $blueprint
     *
     * @throws GenerationException
     * @throws JsonException
     */
    public function create(array $blueprint, string $destination): GenerationJob
    {
        $id = bin2hex(random_bytes(8));
        $now = date('c');
        $dir = $this->directory();
        $blueprintPath = $dir . '/' . $id . '.blueprint.json';
        $logPath = $dir . '/' . $id . '.log';
        $metaPath = $this->metaPath($id);

        $json = json_encode($blueprint, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (file_put_contents($blueprintPath, $json) === false) {
            throw new GenerationException('Écriture blueprint job impossible.');
        }

        $job = new GenerationJob(
            id: $id,
            status: GenerationJob::STATUS_QUEUED,
            destination: $destination,
            blueprintPath: $blueprintPath,
            createdAt: $now,
            updatedAt: $now,
            report: null,
            error: null,
            logPath: $logPath,
        );
        $this->save($job);

        return $job;
    }

    public function get(string $id): ?GenerationJob
    {
        if (!$this->isValidId($id)) {
            return null;
        }
        $path = $this->metaPath($id);
        if (!is_file($path)) {
            return null;
        }
        $raw = file_get_contents($path);
        if ($raw === false) {
            return null;
        }
        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (!isset($data['id'], $data['status'], $data['destination'], $data['blueprint_path'])) {
            return null;
        }

        return GenerationJob::fromArray([
            'id' => (string) $data['id'],
            'status' => (string) $data['status'],
            'destination' => (string) $data['destination'],
            'blueprint_path' => (string) $data['blueprint_path'],
            'created_at' => (string) ($data['created_at'] ?? ''),
            'updated_at' => (string) ($data['updated_at'] ?? ''),
            'report' => isset($data['report']) && is_array($data['report']) ? $data['report'] : null,
            'error' => isset($data['error']) ? (string) $data['error'] : null,
            'log_path' => (string) ($data['log_path'] ?? ''),
        ]);
    }

    public function save(GenerationJob $job): void
    {
        $path = $this->metaPath($job->id);
        $json = json_encode($job->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (file_put_contents($path, $json) === false) {
            throw new GenerationException('Écriture job impossible : ' . $path);
        }
    }

    /**
     * @param ?array<string, mixed> $report
     */
    public function updateStatus(
        string $id,
        string $status,
        ?array $report = null,
        ?string $error = null,
    ): GenerationJob {
        $job = $this->get($id);
        if ($job === null) {
            throw new GenerationException('Job introuvable : ' . $id);
        }

        $updated = new GenerationJob(
            id: $job->id,
            status: $status,
            destination: $job->destination,
            blueprintPath: $job->blueprintPath,
            createdAt: $job->createdAt,
            updatedAt: date('c'),
            report: $report ?? $job->report,
            error: $error,
            logPath: $job->logPath,
        );
        $this->save($updated);

        return $updated;
    }

    private function metaPath(string $id): string
    {
        return $this->directory() . '/' . $id . '.json';
    }

    private function isValidId(string $id): bool
    {
        return (bool) preg_match('/^[a-f0-9]{16}$/', $id);
    }
}
