<?php

declare(strict_types=1);

namespace App\Forge\Job;

/**
 * Job de génération asynchrone (Apache Windows → process CLI détaché).
 *
 * @phpstan-type JobArray array{
 *     id: string,
 *     status: string,
 *     destination: string,
 *     blueprint_path: string,
 *     created_at: string,
 *     updated_at: string,
 *     report: ?array<string, mixed>,
 *     error: ?string,
 *     log_path: string
 * }
 */
final class GenerationJob
{
    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILED = 'failed';

    /**
     * @param ?array<string, mixed> $report
     */
    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly string $destination,
        public readonly string $blueprintPath,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?array $report,
        public readonly ?string $error,
        public readonly string $logPath,
    ) {
    }

    /**
     * @return JobArray
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'destination' => $this->destination,
            'blueprint_path' => $this->blueprintPath,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'report' => $this->report,
            'error' => $this->error,
            'log_path' => $this->logPath,
        ];
    }

    /**
     * @param JobArray $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            status: (string) $data['status'],
            destination: (string) $data['destination'],
            blueprintPath: (string) $data['blueprint_path'],
            createdAt: (string) $data['created_at'],
            updatedAt: (string) $data['updated_at'],
            report: isset($data['report']) && is_array($data['report']) ? $data['report'] : null,
            error: isset($data['error']) ? (string) $data['error'] : null,
            logPath: (string) ($data['log_path'] ?? ''),
        );
    }

    public function isFinished(): bool
    {
        return $this->status === self::STATUS_DONE || $this->status === self::STATUS_FAILED;
    }
}
