<?php

declare(strict_types=1);

namespace App\Forge\Job;

use JsonException;
use RuntimeException;

/**
 * Persistance JSON des jobs CLI atelier.
 */
final class CliJobStore
{
    private readonly string $directory;

    public function __construct(?string $directory = null)
    {
        $this->directory = $directory
            ?? (BASE_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'forge-cli-jobs');
        if (!is_dir($this->directory) && !mkdir($this->directory, 0755, true) && !is_dir($this->directory)) {
            throw new RuntimeException('Impossible de créer storage/forge-cli-jobs.');
        }
    }

    /**
     * @param list<string> $argv
     */
    public function create(string $type, string $projectPath, array $argv): CliJob
    {
        $id = bin2hex(random_bytes(8));
        $job = new CliJob(
            id: $id,
            type: $type,
            projectPath: $projectPath,
            argv: $argv,
            status: CliJob::STATUS_QUEUED,
            createdAt: date('c'),
        );
        $this->write($job);

        return $job;
    }

    public function get(string $id): ?CliJob
    {
        if (!preg_match('/^[a-f0-9]{16}$/', $id)) {
            return null;
        }
        $file = $this->path($id);
        if (!is_file($file)) {
            return null;
        }
        $raw = file_get_contents($file);
        if ($raw === false) {
            return null;
        }
        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return CliJob::fromArray($data);
    }

    public function updateStatus(
        string $id,
        string $status,
        ?string $output = null,
        ?string $error = null,
    ): void {
        $job = $this->get($id);
        if ($job === null) {
            return;
        }
        $job->status = $status;
        if ($output !== null) {
            $job->output = $output;
        }
        if ($error !== null) {
            $job->error = $error;
        }
        if ($status === CliJob::STATUS_DONE || $status === CliJob::STATUS_FAILED) {
            $job->finishedAt = date('c');
        }
        $this->write($job);
    }

    private function write(CliJob $job): void
    {
        $json = json_encode($job->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (file_put_contents($this->path($job->id), $json . "\n") === false) {
            throw new RuntimeException('Écriture job CLI impossible.');
        }
    }

    private function path(string $id): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . $id . '.json';
    }
}
