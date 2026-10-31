<?php

declare(strict_types=1);

namespace App\Forge\Report;

final class GenerationReport
{
    /**
     * @param list<array{operation: string, path: string, status: string, message: string}> $entries
     * @param list<string> $errors
     */
    public function __construct(
        public readonly bool $success,
        public readonly bool $dryRun,
        public readonly string $destination,
        public readonly array $entries = [],
        public readonly array $errors = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'dryRun' => $this->dryRun,
            'destination' => $this->destination,
            'entries' => $this->entries,
            'errors' => $this->errors,
        ];
    }
}
