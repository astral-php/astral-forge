<?php

declare(strict_types=1);

namespace App\Forge;

use App\Forge\Blueprint\BlueprintValidator;
use App\Forge\Exception\GenerationException;
use App\Forge\Job\DetachedJobLauncher;
use App\Forge\Job\GenerationJob;
use App\Forge\Job\GenerationJobStore;
use App\Forge\Plan\GenerationPlan;
use App\Forge\Plan\GenerationPlanner;
use App\Forge\Report\GenerationReport;
use App\Forge\Resolver\DependencyResolver;
use App\Forge\Security\SecurePathGuard;
use App\Forge\Writer\ComposerCreateProjectRunner;
use App\Forge\Writer\PlanExecutor;

/**
 * Orchestre validation → résolution → plan → (optionnel) écriture / job async.
 */
final class ProjectGenerationService
{
    public function __construct(
        private readonly BlueprintValidator $validator,
        private readonly DependencyResolver $resolver,
        private readonly GenerationPlanner $planner,
        private readonly SecurePathGuard $pathGuard,
        private readonly PlanExecutor $executor,
        private readonly ComposerCreateProjectRunner $composerRunner = new ComposerCreateProjectRunner(),
        private readonly GenerationJobStore $jobStore = new GenerationJobStore(),
        private readonly DetachedJobLauncher $jobLauncher = new DetachedJobLauncher(),
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function environmentStatus(): array
    {
        $status = $this->composerRunner->environmentStatus();
        $syncAllowed = ($status['web_generate_allowed'] ?? true) === true;
        $status['web_generate_mode'] = $syncAllowed ? 'sync' : 'async';
        // L’UI peut toujours générer (sync ou job détaché)
        $status['web_generate_allowed'] = true;
        $status['web_generate_hint'] = $syncAllowed
            ? null
            : 'Sous Apache Windows, la génération se lance en arrière-plan depuis l’interface (sans CLI).';

        return $status;
    }

    public function getJob(string $id): ?GenerationJob
    {
        return $this->jobStore->get($id);
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
     * Génération web : sync si possible, sinon job CLI détaché (Apache Win).
     *
     * @param array<string, mixed> $blueprintData
     *
     * @return array{mode: 'sync', report: GenerationReport}|array{mode: 'async', job: GenerationJob}
     */
    public function generateForWeb(
        array $blueprintData,
        string $destination,
        bool $confirm = false,
    ): array {
        $plan = $this->plan($blueprintData, $destination);

        if (!$confirm) {
            throw new GenerationException(
                'Génération refusée : confirmation requise (CLI --confirm ou action web Confirm).'
            );
        }

        $runtime = $this->composerRunner->environmentStatus();
        $syncOk = ($runtime['web_generate_allowed'] ?? true) === true;

        if ($syncOk) {
            $this->composerRunner->assertReady();
            $report = $this->executor->execute($plan, false);

            return ['mode' => 'sync', 'report' => $report];
        }

        $job = $this->jobStore->create($blueprintData, $plan->destination);
        $this->jobLauncher->launch($job->id);

        return ['mode' => 'async', 'job' => $job];
    }

    /**
     * @param array<string, mixed> $blueprintData
     */
    public function generate(
        array $blueprintData,
        string $destination,
        bool $confirm = false,
        bool $dryRun = false,
        bool $allowAsyncFallback = true,
    ): GenerationReport {
        $plan = $this->plan($blueprintData, $destination);

        if (!$dryRun && !$confirm) {
            throw new GenerationException(
                'Génération refusée : confirmation requise (CLI --confirm ou action web Confirm).'
            );
        }

        if (!$dryRun) {
            $runtime = $this->composerRunner->environmentStatus();
            $syncOk = ($runtime['web_generate_allowed'] ?? true) === true;
            if (!$syncOk) {
                if ($allowAsyncFallback) {
                    throw new GenerationException(
                        'Génération synchrone indisponible sous Apache Windows. '
                        . 'Utilisez l’UI web (job arrière-plan) ou la CLI `php bin/forge generate`.'
                    );
                }
                // Worker forge-job : SAPI=cli → syncOk true en pratique ; filet de sécurité
            }
            // assertReady refuse Apache ; en CLI c’est OK. Pour worker on a allowApacheComposer.
            if ($syncOk || !$allowAsyncFallback) {
                if ($syncOk) {
                    $this->composerRunner->assertReady();
                } else {
                    $this->composerRunner->toolchain()->assertReady();
                }
            }
        }

        return $this->executor->execute($plan, $dryRun);
    }
}
