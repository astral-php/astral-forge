<?php

declare(strict_types=1);

namespace App\Forge\Plan;

/**
 * Une opération du plan de génération (sans I/O tant que non exécutée).
 */
final class PlanOperation
{
    public const TYPE_ENSURE_EMPTY_DIR = 'ensure_empty_dir';
    public const TYPE_COMPOSER_CREATE_PROJECT = 'composer_create_project';
    public const TYPE_WRITE_CRUD_MODULE = 'write_crud_module';
    public const TYPE_WRITE_API_BOOTSTRAP = 'write_api_bootstrap';
    public const TYPE_NOTE = 'note';

    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public readonly string $type,
        public readonly string $path,
        public readonly array $meta = [],
        public readonly string $description = '',
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'path' => $this->path,
            'meta' => $this->meta,
            'description' => $this->description,
        ];
    }
}
