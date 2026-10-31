<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Forge\Exception\GenerationException;
use App\Forge\Exception\InvalidBlueprintException;
use App\Forge\Exception\UnsafePathException;
use App\Forge\ProjectGenerationService;
use Controller\AbstractController;
use Core\Http\JsonResponse;
use Core\Request;
use Core\View;

/**
 * Endpoints JSON pour l’UI Vue (plan / generate).
 */
final class ForgeApiController extends AbstractController
{
    public function __construct(
        View $view,
        private readonly Request $request,
        private readonly ProjectGenerationService $forge,
    ) {
        parent::__construct($view);
    }

    public function plan(): JsonResponse
    {
        $form = $this->formFromJson();

        try {
            $plan = $this->forge->plan($this->blueprintFromForm($form), $form['destination']);

            return $this->json([
                'ok' => true,
                'plan' => $plan->toArray(),
            ]);
        } catch (InvalidBlueprintException | UnsafePathException $e) {
            return $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function generate(): JsonResponse
    {
        $form = $this->formFromJson();
        $confirm = ($form['confirm'] ?? false) === true || ($form['confirm'] ?? '') === '1';

        if (!$confirm) {
            return $this->json([
                'ok' => false,
                'error' => 'Confirmation requise avant génération.',
            ], 422);
        }

        try {
            $report = $this->forge->generate(
                $this->blueprintFromForm($form),
                $form['destination'],
                confirm: true,
                dryRun: false,
            );

            return $this->json([
                'ok' => $report->success,
                'report' => $report->toArray(),
                'error' => $report->success ? null : implode(' ', $report->errors),
            ], $report->success ? 200 : 422);
        } catch (InvalidBlueprintException | UnsafePathException | GenerationException $e) {
            return $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /**
     * @return array{project_name: string, destination: string, recipe: string, confirm?: bool|string}
     */
    private function formFromJson(): array
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->request->post();

        return [
            'project_name' => trim((string) ($payload['project_name'] ?? 'mon-application')),
            'destination' => trim((string) ($payload['destination'] ?? '')),
            'recipe' => trim((string) ($payload['recipe'] ?? 'blank')),
            'confirm' => $payload['confirm'] ?? false,
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
