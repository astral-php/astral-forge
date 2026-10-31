<?php

declare(strict_types=1);

namespace App\Forge\Plan;

use App\Forge\Blueprint\Blueprint;
use App\Forge\Exception\InvalidBlueprintException;

/**
 * Produit un plan déterministe sans écrire de fichiers.
 */
final class GenerationPlanner
{
    public function __construct(
        private readonly string $starterPackage = 'astral-php/astral-starter:^2.1',
    ) {
    }

    public function plan(Blueprint $blueprint, string $absoluteDestination): GenerationPlan
    {
        $allowed = ['blank', 'blog-lite', 'api-json'];
        if (!in_array($blueprint->recipe, $allowed, true)) {
            throw new InvalidBlueprintException(
                'Recette non planifiable : ' . $blueprint->recipe
            );
        }

        $dest = $this->normalizePath($absoluteDestination);
        $operations = [
            new PlanOperation(
                type: PlanOperation::TYPE_ENSURE_EMPTY_DIR,
                path: $dest,
                description: 'Vérifier que la destination est absente ou vide.',
            ),
            new PlanOperation(
                type: PlanOperation::TYPE_COMPOSER_CREATE_PROJECT,
                path: $dest,
                meta: [
                    'package' => $this->starterPackage,
                    'project_name' => $blueprint->projectName,
                    'no_interaction' => true,
                ],
                description: sprintf(
                    'composer create-project %s → %s (APP_NAME=%s)',
                    $this->starterPackage,
                    $dest,
                    $blueprint->projectName
                ),
            ),
        ];

        foreach ($blueprint->modules as $module) {
            $operations[] = new PlanOperation(
                type: PlanOperation::TYPE_WRITE_CRUD_MODULE,
                path: $dest,
                meta: [
                    'entity' => $module,
                    'public_list' => $blueprint->recipe === 'blog-lite',
                ],
                description: sprintf(
                    'Module CRUD PHP : %s (Model, Dao, Controller, migration, vues, routes)',
                    $module
                ),
            );
        }

        if ($blueprint->features['api'] ?? false) {
            $operations[] = new PlanOperation(
                type: PlanOperation::TYPE_WRITE_API_BOOTSTRAP,
                path: $dest,
                meta: [
                    'prefix' => '/api/v1',
                ],
                description: 'Bootstrap API JSON : HealthController + routes /api/v1 + Cors.',
            );
        }

        $operations[] = new PlanOperation(
            type: PlanOperation::TYPE_NOTE,
            path: $dest,
            meta: [
                'recipe' => $blueprint->recipe,
                'packages' => $blueprint->packages,
            ],
            description: match ($blueprint->recipe) {
                'blog-lite' => 'Recette blog-lite : starter + module Article (liste publique).',
                'api-json' => 'Recette api-json : starter + API v1 (Cors).',
                default => 'Recette blank : auth + admin users inclus via astral-starter.',
            },
        );

        if ($blueprint->packages !== []) {
            $operations[] = new PlanOperation(
                type: PlanOperation::TYPE_NOTE,
                path: $dest,
                meta: ['packages' => $blueprint->packages],
                description: 'Packages déclarés (à installer manuellement si besoin) : '
                    . implode(', ', $blueprint->packages),
            );
        }

        return new GenerationPlan($blueprint, $dest, $operations);
    }

    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
