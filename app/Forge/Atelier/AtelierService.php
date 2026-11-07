<?php

declare(strict_types=1);

namespace App\Forge\Atelier;

use App\Forge\Exception\GenerationException;
use App\Forge\Generator\CrudModuleWriter;
use App\Forge\Job\CliJob;
use App\Models\ForgeProject;
use InvalidArgumentException;

/**
 * Actions atelier post-génération : migrate, module, purge.
 */
final class AtelierService
{
    public function __construct(
        private readonly ProjectRegistry $registry,
        private readonly ProjectConsoleRunner $console,
        private readonly ProjectDirectoryRemover $directoryRemover,
        private readonly CrudModuleWriter $moduleWriter = new CrudModuleWriter(),
    ) {
    }

    /**
     * @return array{mode: 'sync', ok: bool, output: string, error: ?string, project: ForgeProject}
     *         |array{mode: 'async', job: CliJob, project: ForgeProject}
     */
    public function migrate(int $projectId): array
    {
        return $this->runConsole($projectId, 'migrate', ['migrate']);
    }

    /**
     * @return array{mode: 'sync', ok: bool, output: string, error: ?string, project: ForgeProject}
     *         |array{mode: 'async', job: CliJob, project: ForgeProject}
     */
    public function migrateRollback(int $projectId): array
    {
        return $this->runConsole($projectId, 'migrate:rollback', ['migrate:rollback']);
    }

    /**
     * @param list<string> $argv
     *
     * @return array{mode: 'sync', ok: bool, output: string, error: ?string, project: ForgeProject}
     *         |array{mode: 'async', job: CliJob, project: ForgeProject}
     */
    private function runConsole(int $projectId, string $type, array $argv): array
    {
        $project = $this->registry->requireExisting($projectId);
        $result = $this->console->run($project->path, $type, $argv);
        $this->registry->touch($project->id);
        $result['project'] = $project;

        return $result;
    }

    /**
     * @return array{ok: bool, files: list<string>, project: ForgeProject, error: ?string}
     */
    public function addModule(int $projectId, string $entity, bool $confirm): array
    {
        if (!$confirm) {
            throw new GenerationException('Confirmation requise avant de créer un module.');
        }

        $entity = trim($entity);
        if (!preg_match('/^[A-Z][a-zA-Z0-9]*$/', $entity)) {
            throw new GenerationException(
                'Nom d’entité invalide (PascalCase attendu, ex. Article).'
            );
        }

        $reserved = ['User', 'Home', 'Auth', 'Profile', 'Admin', 'Api', 'Forge'];
        if (in_array($entity, $reserved, true)) {
            throw new GenerationException(
                'Entité réservée : ' . $entity . '. Choisissez un autre nom (ex. Task, Category).'
            );
        }

        $project = $this->registry->requireExisting($projectId);
        // Liste + fiche publiques : parcours web local sans mur login après création.
        $files = $this->moduleWriter->write($project->path, $entity, publicList: true);
        $this->registry->touch($project->id);

        return [
            'ok' => true,
            'files' => $files,
            'project' => $project,
            'error' => null,
        ];
    }

    public function getCliJob(string $id): ?CliJob
    {
        return $this->console->getJob($id);
    }

    /**
     * Efface le dossier projet (si présent) puis retire l’entrée du registre.
     *
     * @throws GenerationException
     * @throws InvalidArgumentException
     */
    public function purgeProject(int $projectId, bool $confirm): void
    {
        if (!$confirm) {
            throw new GenerationException(
                'Confirmation requise avant de supprimer le projet (action irréversible).'
            );
        }

        $project = $this->registry->find($projectId);
        if ($project === null) {
            throw new InvalidArgumentException('Projet introuvable dans le registre.');
        }

        if ($project->directoryExists()) {
            $this->directoryRemover->delete($project->path);
        }

        $this->registry->remove($project->id);
    }
}
