<?php

declare(strict_types=1);

namespace App\Forge;

use App\Forge\Blueprint\BlueprintValidator;
use App\Forge\Exception\GenerationException;
use App\Forge\Plan\GenerationPlan;
use App\Forge\Plan\GenerationPlanner;
use App\Forge\Report\GenerationReport;
use App\Forge\Resolver\DependencyResolver;
use App\Forge\Security\SecurePathGuard;
use App\Forge\Writer\PlanExecutor;

/**
 * Orchestre validation → résolution → plan → (optionnel) écriture.
 */
final class ProjectGenerationService
{
    public function __construct(
        private readonly BlueprintValidator $validator,
        private readonly DependencyResolver $resolver,
        private readonly GenerationPlanner $planner,
        private readonly SecurePathGuard $pathGuard,
        private readonly PlanExecutor $executor,
    ) {
    }

    /**
     * @param array<string, mixed> $blueprintData
     */
    public function plan(array $blueprintData, string $destination): GenerationPlan
    {
        $blueprint = $this->validator->validate($blueprintData);
        $this->resolver->assertCompatible($blueprint);
        $absolute = $this->pathGuard->resolveDestination($destination);

        return $this->planner->plan($blueprint, $absolute);
    }

    /**
     * @param array<string, mixed> $blueprintData
     */
    public function generate(
        array $blueprintData,
        string $destination,
        bool $confirm = false,
        bool $dryRun = false,
    ): GenerationReport {
        $plan = $this->plan($blueprintData, $destination);

        if (!$dryRun && !$confirm) {
            throw new GenerationException(
                'Génération refusée : confirmation requise (CLI --confirm ou action web Confirm).'
            );
        }

        return $this->executor->execute($plan, $dryRun);
    }
}
