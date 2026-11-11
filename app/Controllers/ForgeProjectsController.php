<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Forge\Atelier\AtelierService;
use App\Forge\Atelier\ProjectInspector;
use App\Forge\Atelier\ProjectMigrationInspector;
use App\Forge\Atelier\ProjectRegistry;
use App\Forge\Exception\GenerationException;
use App\Forge\Exception\UnsafePathException;
use App\Forge\Generator\EntityNaming;
use App\Forge\Install\InstallGate;
use App\Models\ForgeProject;
use Controller\AbstractController;
use Core\Http\RedirectResponse;
use Core\Http\Response;
use Core\Request;
use Core\Session;
use Core\View;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Atelier — Mes projets (liste / fiche / retirer / migrate / module).
 */
final class ForgeProjectsController extends AbstractController
{
    public function __construct(
        View $view,
        private readonly Request $request,
        private readonly Session $session,
        private readonly ProjectRegistry $registry,
        private readonly AtelierService $atelier,
        private readonly InstallGate $install,
        private readonly ProjectInspector $inspector = new ProjectInspector(),
        private readonly ProjectMigrationInspector $migrationInspector = new ProjectMigrationInspector(),
    ) {
        parent::__construct($view);
    }

    private function redirectTo(string $url): RedirectResponse
    {
        return RedirectResponse::to($url);
    }

    public function index(): Response
    {
        if ($this->install->needsMigrate()) {
            return $this->redirectTo('/setup');
        }

        $projects = $this->registry->all();

        return $this->render('forge/projects', [
            'title' => 'Mes projets — Astral Forge',
            'projects' => $projects,
        ]);
    }

    public function show(string $id): Response
    {
        $project = $this->registry->find((int) $id);
        if ($project === null) {
            $this->session->flash('error', 'Projet introuvable.');

            return $this->redirectTo('/forge');
        }

        $cliJobId = trim((string) $this->request->query('job', ''));
        $cliJobLabel = trim((string) $this->request->query('job_label', 'Migration'));
        if ($cliJobLabel === '') {
            $cliJobLabel = 'Migration';
        }

        return $this->renderShow(
            $project,
            cliJobId: $cliJobId !== '' ? $cliJobId : null,
            cliJobLabel: $cliJobLabel,
        );
    }

    public function destroy(string $id): Response
    {
        $project = $this->registry->find((int) $id);
        if ($project === null) {
            $this->session->flash('error', 'Projet introuvable.');

            return $this->redirectTo('/forge');
        }

        $this->registry->remove($project->id);
        $this->session->flash('success', 'Projet retiré du registre (dossier inchangé).');

        return $this->redirectTo('/forge');
    }

    public function purge(string $id): Response
    {
        $confirm = $this->request->input('confirm') === '1';

        try {
            $this->atelier->purgeProject((int) $id, $confirm);
        } catch (InvalidArgumentException | RuntimeException | UnsafePathException | GenerationException $e) {
            $this->session->flash('error', $e->getMessage());

            return $this->redirectTo('/forge/projects/' . $id);
        } catch (Throwable $e) {
            $this->session->flash('error', $e->getMessage());

            return $this->redirectTo('/forge/projects/' . $id);
        }

        $this->session->flash('success', 'Projet supprimé du disque et du registre.');

        return $this->redirectTo('/forge');
    }

    public function migrate(string $id): Response
    {
        return $this->runDbAction((int) $id, 'migrate', 'Migration');
    }

    public function migrateRollback(string $id): Response
    {
        $confirm = $this->request->input('confirm') === '1';
        if (!$confirm) {
            $this->session->flash('error', 'Cochez la confirmation avant un rollback.');

            return $this->redirectTo('/forge/projects/' . $id);
        }

        return $this->runDbAction((int) $id, 'rollback', 'Rollback');
    }

    /**
     * @param 'migrate'|'rollback' $action
     */
    private function runDbAction(int $id, string $action, string $label): Response
    {
        try {
            $result = $action === 'rollback'
                ? $this->atelier->migrateRollback($id)
                : $this->atelier->migrate($id);
        } catch (InvalidArgumentException | RuntimeException | UnsafePathException | GenerationException $e) {
            $this->session->flash('error', $e->getMessage());

            return $this->redirectTo('/forge/projects/' . $id);
        } catch (Throwable $e) {
            $this->session->flash('error', $e->getMessage());

            return $this->redirectTo('/forge/projects/' . $id);
        }

        $project = $result['project'];

        if ($result['mode'] === 'async') {
            return $this->redirectTo($this->projectJobUrl($project->id, $result['job']->id, $label));
        }

        if ($result['ok']) {
            $this->session->flash('success', $label . ' OK.');
        } else {
            $this->session->flash(
                'error',
                $label . ' en échec (projet toujours enregistré) : '
                . ($result['error'] ?? 'erreur inconnue')
            );
        }

        return $this->redirectTo('/forge/projects/' . $project->id);
    }

    private function projectJobUrl(int $projectId, string $jobId, string $label): string
    {
        return '/forge/projects/' . $projectId
            . '?job=' . rawurlencode($jobId)
            . '&job_label=' . rawurlencode($label);
    }

    public function addModule(string $id): Response
    {
        $entity = trim((string) $this->request->input('entity', ''));
        $confirm = $this->request->input('confirm') === '1';

        $project = $this->registry->find((int) $id);
        if ($project === null) {
            $this->session->flash('error', 'Projet introuvable.');

            return $this->redirectTo('/forge');
        }

        try {
            $result = $this->atelier->addModule((int) $id, $entity, $confirm);
        } catch (InvalidArgumentException | RuntimeException | UnsafePathException | GenerationException $e) {
            return $this->renderShow(
                $project,
                moduleResult: ['error' => $e->getMessage(), 'entity' => $entity],
            );
        }

        $fileCount = count($result['files']);
        $project = $result['project'];

        // Parcours web : écrire le module puis migrer tout de suite (table prête).
        try {
            $migrate = $this->atelier->migrate((int) $id);
        } catch (Throwable $e) {
            $this->session->flash(
                'success',
                'Module ' . $entity . ' créé (' . $fileCount . ' fichiers).'
            );
            $this->session->flash(
                'error',
                'Migration non lancée : ' . $e->getMessage() . ' — utilisez « Migrer ».'
            );

            return $this->redirectTo('/forge/projects/' . $id);
        }

        if ($migrate['mode'] === 'async') {
            $this->session->flash(
                'success',
                'Module ' . $entity . ' créé (' . $fileCount . ' fichiers). Suivi de la migration ci-dessous.'
            );

            return $this->redirectTo($this->projectJobUrl((int) $id, $migrate['job']->id, 'Migration'));
        }

        if ($migrate['ok']) {
            $route = '/' . EntityNaming::toSnakePlural($entity);
            $this->session->flash(
                'success',
                'Module ' . $entity . ' créé et migré (' . $fileCount . ' fichiers). '
                . 'Ouvrir ' . $route . ' dans l’app. '
                . 'Ajoutez ce lien dans le layout (nav non générée automatiquement).'
            );
        } else {
            $this->session->flash(
                'success',
                'Module ' . $entity . ' créé (' . $fileCount . ' fichiers).'
            );
            $this->session->flash(
                'error',
                'Migration en échec (fichiers OK) : ' . ($migrate['error'] ?? 'erreur inconnue')
            );
        }

        return $this->redirectTo('/forge/projects/' . $id);
    }

    /**
     * @param array{error?: string, entity?: string}|null $moduleResult
     */
    private function renderShow(
        ForgeProject $project,
        ?array $moduleResult = null,
        ?string $cliJobId = null,
        string $cliJobLabel = 'Migration',
    ): Response {
        $exists = $project->directoryExists();

        return $this->render('forge/project-show', [
            'title' => $project->name . ' — Astral Forge',
            'project' => $project,
            'exists' => $exists,
            'modules' => $exists ? $this->inspector->modules($project) : [],
            'migrationStatus' => $exists ? $this->migrationInspector->inspect($project) : null,
            'moduleResult' => $moduleResult,
            'cliJobId' => $cliJobId,
            'cliJobLabel' => $cliJobLabel,
        ]);
    }
}
