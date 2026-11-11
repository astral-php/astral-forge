<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Forge\Install\InstallGate;
use Controller\AbstractController;
use Core\Http\RedirectResponse;
use Core\Http\Response;
use Core\Session;
use Core\View;
use Throwable;

/**
 * Setup web minimal — installer / migrer la BDD Forge si migrations en attente.
 */
final class SetupController extends AbstractController
{
    public function __construct(
        View $view,
        private readonly Session $session,
        private readonly InstallGate $install,
    ) {
        parent::__construct($view);
    }

    public function index(): Response
    {
        $summary = $this->install->summary();

        if (!$summary['needs_migrate'] && $summary['error'] === null) {
            $this->session->flash('success', 'Forge est déjà installé (migrations à jour).');

            return RedirectResponse::to('/forge');
        }

        return $this->render('setup/index', [
            'title' => 'Installation — Astral Forge',
            'summary' => $summary,
            'cliHint' => 'php bin/console migrate',
        ]);
    }

    public function migrate(): Response
    {
        try {
            $executed = $this->install->migrate();
            if ($executed === []) {
                $this->session->flash('success', 'Aucune migration en attente — Forge est prêt.');
            } else {
                $this->session->flash(
                    'success',
                    'Installation OK : ' . count($executed) . ' migration(s) appliquée(s).'
                );
            }

            return RedirectResponse::to('/forge');
        } catch (Throwable $e) {
            $this->session->flash('error', 'Migration échouée : ' . $e->getMessage());

            return RedirectResponse::to('/setup');
        }
    }
}
