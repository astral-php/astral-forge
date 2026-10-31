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
        if ($blueprint->recipe !== 'blank') {
            throw new InvalidBlueprintException('Seule la recette blank est planifiable en 0.1.');
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
            new PlanOperation(
                type: PlanOperation::TYPE_NOTE,
                path: $dest,
                meta: [
                    'authentication' => $blueprint->features['authentication'] ?? true,
                    'admin_users' => $blueprint->features['admin_users'] ?? true,
                ],
                description: 'Recette blank : auth + admin users inclus via astral-starter.',
            ),
        ];

        return new GenerationPlan($blueprint, $dest, $operations);
    }

    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
