<?php

declare(strict_types=1);

namespace App\Forge\Blueprint;

/**
 * Blueprint validé (schéma v1) — jamais exécuté comme du PHP.
 */
final class Blueprint
{
    /**
     * @param array{
     *     authentication?: bool,
     *     admin_users?: bool,
     *     api?: bool,
     *     crud_resource?: bool
     * } $features
     * @param list<string> $packages
     * @param list<string> $modules Noms d’entités PascalCase (ex. Article)
     */
    public function __construct(
        public readonly int $schemaVersion,
        public readonly string $projectName,
        public readonly string $target,
        public readonly string $recipe,
        public readonly array $features,
        public readonly string $viewsEngine,
        public readonly array $packages,
        public readonly array $modules = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'schemaVersion' => $this->schemaVersion,
            'project' => [
                'name' => $this->projectName,
                'target' => $this->target,
            ],
            'recipe' => $this->recipe,
            'features' => $this->features,
            'modules' => $this->modules,
            'views' => [
                'engine' => $this->viewsEngine,
            ],
            'packages' => $this->packages,
        ];
    }
}
