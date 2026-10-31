<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Forge\Exception\GenerationException;
use App\Forge\Exception\InvalidBlueprintException;
use App\Forge\Exception\UnsafePathException;
use App\Forge\ProjectGenerationService;
use Controller\AbstractController;
use Core\Http\RedirectResponse;
use Core\Http\Response;
use Core\Request;
use Core\Session;
use Core\View;

/**
 * UI PHP minimale 0.2 — blank / blog-lite / api-json (Vite UI = 0.3).
 */
final class ForgeController extends AbstractController
{
    public function __construct(
        View $view,
        private readonly Request $request,
        private readonly Session $session,
        private readonly ProjectGenerationService $forge,
    ) {
        parent::__construct($view);
    }

    public function index(): Response
    {
        return $this->render('forge/index', [
            'title' => 'Forge — nouveau projet',
            'form' => $this->defaultForm(),
            'plan' => null,
            'report' => null,
        ]);
    }

    public function plan(): Response
    {
        $form = $this->formFromRequest();

        try {
            $plan = $this->forge->plan($this->blueprintFromForm($form), $form['destination']);
            $planArray = $plan->toArray();
        } catch (InvalidBlueprintException | UnsafePathException $e) {
            $this->session->flash('error', $e->getMessage());

            return $this->render('forge/index', [
                'title' => 'Forge — nouveau projet',
                'form' => $form,
                'plan' => null,
                'report' => null,
            ]);
        }

        return $this->render('forge/index', [
            'title' => 'Forge — plan',
            'form' => $form,
            'plan' => $planArray,
            'report' => null,
        ]);
    }

    public function generate(): Response
    {
        $form = $this->formFromRequest();
        $confirm = $this->request->input('confirm') === '1';

        if (!$confirm) {
            $this->session->flash('error', 'Cochez la confirmation avant de générer.');

            return new RedirectResponse('/forge');
        }

        try {
            $report = $this->forge->generate(
                $this->blueprintFromForm($form),
                $form['destination'],
                confirm: true,
                dryRun: false,
            );
        } catch (InvalidBlueprintException | UnsafePathException | GenerationException $e) {
            $this->session->flash('error', $e->getMessage());

            return $this->render('forge/index', [
                'title' => 'Forge — erreur',
                'form' => $form,
                'plan' => null,
                'report' => null,
            ]);
        }

        if ($report->success) {
            $this->session->flash('success', 'Projet généré : ' . $report->destination);
        } else {
            $this->session->flash('error', implode(' ', $report->errors));
        }

        return $this->render('forge/index', [
            'title' => 'Forge — résultat',
            'form' => $form,
            'plan' => null,
            'report' => $report->toArray(),
        ]);
    }

    /**
     * @return array{project_name: string, destination: string, recipe: string}
     */
    private function defaultForm(): array
    {
        return [
            'project_name' => 'mon-application',
            'destination' => '',
            'recipe' => 'blank',
        ];
    }

    /**
     * @return array{project_name: string, destination: string, recipe: string}
     */
    private function formFromRequest(): array
    {
        return [
            'project_name' => trim((string) $this->request->input('project_name', 'mon-application')),
            'destination' => trim((string) $this->request->input('destination', '')),
            'recipe' => trim((string) $this->request->input('recipe', 'blank')),
        ];
    }

    /**
     * @param array{project_name: string, destination: string, recipe: string} $form
     * @return array<string, mixed>
     */
    private function blueprintFromForm(array $form): array
    {
        $recipe = $form['recipe'];

        return [
            'schemaVersion' => 1,
            'project' => [
                'name' => $form['project_name'],
                'target' => 'astral-starter-2.1',
            ],
            'recipe' => $recipe,
            'features' => [
                'authentication' => true,
                'admin_users' => true,
                'api' => $recipe === 'api-json',
                'crud_resource' => $recipe === 'blog-lite',
            ],
            'modules' => $recipe === 'blog-lite' ? ['Article'] : [],
            'views' => [
                'engine' => 'php',
            ],
            'packages' => [],
        ];
    }
}
