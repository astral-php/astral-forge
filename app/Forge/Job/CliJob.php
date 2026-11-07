<?php

declare(strict_types=1);

namespace App\Forge\Job;

/**
 * Job CLI atelier (migrate, …) — suivi JSON sous storage/forge-cli-jobs.
 */
final class CliJob
{
    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILED = 'failed';

    /**
     * @param list<string> $argv arguments après `php bin/console`
     */
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly string $projectPath,
        public readonly array $argv,
        public string $status = self::STATUS_QUEUED,
        public ?string $output = null,
        public ?string $error = null,
        public readonly string $createdAt = '',
        public ?string $finishedAt = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'project_path' => $this->projectPath,
            'argv' => $this->argv,
            'status' => $this->status,
            'output' => $this->output,
            'error' => $this->error,
            'created_at' => $this->createdAt,
            'finished_at' => $this->finishedAt,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        /** @var list<string> $argv */
        $argv = array_values(array_map('strval', $data['argv'] ?? []));

        return new self(
            id: (string) ($data['id'] ?? ''),
            type: (string) ($data['type'] ?? ''),
            projectPath: (string) ($data['project_path'] ?? ''),
            argv: $argv,
            status: (string) ($data['status'] ?? self::STATUS_QUEUED),
            output: isset($data['output']) ? (string) $data['output'] : null,
            error: isset($data['error']) ? (string) $data['error'] : null,
            createdAt: (string) ($data['created_at'] ?? ''),
            finishedAt: isset($data['finished_at']) ? (string) $data['finished_at'] : null,
        );
    }
}
