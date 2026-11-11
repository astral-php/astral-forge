<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Forge\Atelier\AtelierService;
use App\Forge\Exception\GenerationException;
use App\Forge\Exception\InvalidBlueprintException;
use App\Forge\Exception\UnsafePathException;
use App\Forge\Generator\EntityNaming;
use App\Forge\Install\InstallGate;
use App\Forge\ProjectGenerationService;
use App\Forge\Support\Utf8;
use Controller\AbstractController;
use Core\Http\JsonResponse;
use Core\Request;
use Core\View;
use JsonException;
use Throwable;

/**
 * Endpoints JSON pour l’UI Vue (plan / generate / jobs / atelier).
 */
final class ForgeApiController extends AbstractController
{
    public function __construct(
        View $view,
        private readonly Request $request,
        private readonly ProjectGenerationService $forge,
        private readonly AtelierService $atelier,
        private readonly InstallGate $install,
    ) {
        parent::__construct($view);
    }

    public function environment(): JsonResponse
    {
        $status = $this->forge->environmentStatus();
        $install = $this->install->summary();
        $ok = $status['ok'] && !$install['needs_migrate'] && $install['error'] === null;

        return $this->jsonSafe([
            'ok' => $ok,
            'environment' => $status,
            'install' => $install,
            'setup_url' => '/setup',
        ], $ok ? 200 : 503);
    }

    public function plan(): JsonResponse
    {
        $form = $this->formFromJson();

        try {
            $plan = $this->forge->plan($this->blueprintFromForm($form), $form['destination']);

            return $this->jsonSafe([
                'ok' => true,
                'plan' => $plan->toArray(),
                'environment' => $this->forge->environmentStatus(),
            ]);
        } catch (InvalidBlueprintException | UnsafePathException $e) {
            return $this->jsonSafe(['ok' => false, 'error' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return $this->jsonSafe(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function generate(): JsonResponse
    {
        $form = $this->formFromJson();
        $confirm = ($form['confirm'] ?? false) === true || ($form['confirm'] ?? '') === '1';

        if (!$confirm) {
            return $this->jsonSafe([
                'ok' => false,
                'error' => 'Confirmation requise avant génération.',
            ], 422);
        }

        try {
            $result = $this->forge->generateForWeb(
                $this->blueprintFromForm($form),
                $form['destination'],
                confirm: true,
            );

            if ($result['mode'] === 'async') {
                return $this->jsonSafe([
                    'ok' => true,
                    'async' => true,
                    'job' => $result['job']->toArray(),
                    'message' => 'Génération lancée en arrière-plan…',
                ], 202);
            }

            $report = $result['report'];
            $error = $report->success ? null : implode(' ', $report->errors);

            return $this->jsonSafe([
                'ok' => $report->success,
                'async' => false,
                'report' => $report->toArray(),
                'error' => $error,
            ], $report->success ? 200 : 422);
        } catch (InvalidBlueprintException | UnsafePathException | GenerationException $e) {
            return $this->jsonSafe(['ok' => false, 'error' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return $this->jsonSafe(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function job(string $id): JsonResponse
    {
        $job = $this->forge->getJob(trim($id));
        if ($job === null) {
            return $this->jsonSafe(['ok' => false, 'error' => 'Job introuvable.'], 404);
        }

        return $this->jsonSafe([
            'ok' => true,
            'job' => $job->toArray(),
        ]);
    }

    public function cliJob(string $id): JsonResponse
    {
        $job = $this->atelier->getCliJob(trim($id));
        if ($job === null) {
            return $this->jsonSafe(['ok' => false, 'error' => 'Job CLI introuvable.'], 404);
        }

        return $this->jsonSafe([
            'ok' => true,
            'job' => $job->toArray(),
        ]);
    }

    public function entityPreview(): JsonResponse
    {
        $name = trim((string) $this->request->query('name', ''));
        $preview = EntityNaming::preview($name);

        return $this->jsonSafe([
            'ok' => $preview['ok'] && $preview['error'] === null,
            'preview' => $preview,
        ], $preview['error'] === null ? 200 : 422);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function jsonSafe(array $data, int $status = 200): JsonResponse
    {
        /** @var array<string, mixed> $safe */
        $safe = Utf8::deep($data);

        try {
            return $this->json($safe, $status);
        } catch (JsonException) {
            return $this->json([
                'ok' => false,
                'error' => 'Réponse JSON invalide (encodage). Consultez storage/logs.',
            ], 500);
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
