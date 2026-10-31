<?php

declare(strict_types=1);

namespace Tests\Forge;

use App\Forge\Blueprint\BlueprintValidator;
use App\Forge\Plan\GenerationPlanner;
use App\Forge\ProjectGenerationService;
use App\Forge\Resolver\DependencyResolver;
use App\Forge\Security\SecurePathGuard;
use App\Forge\Writer\ComposerCreateProjectRunner;
use App\Forge\Writer\PlanExecutor;
use PHPUnit\Framework\TestCase;

final class ForgeApiPlanTest extends TestCase
{
    public function testServicePlanForVuePayloadShape(): void
    {
        $root = sys_get_temp_dir();
        $service = new ProjectGenerationService(
            new BlueprintValidator(),
            new DependencyResolver(),
            new GenerationPlanner(),
            new SecurePathGuard([$root], dirname(__DIR__, 2)),
            new PlanExecutor(new ComposerCreateProjectRunner()),
        );

        $dest = $root . DIRECTORY_SEPARATOR . 'forge-vue-plan-' . bin2hex(random_bytes(3));
        $plan = $service->plan([
            'schemaVersion' => 1,
            'project' => ['name' => 'vue-demo', 'target' => 'astral-starter-2.1'],
            'recipe' => 'blank',
            'features' => [
                'authentication' => true,
                'admin_users' => true,
                'api' => false,
                'crud_resource' => false,
            ],
            'views' => ['engine' => 'php'],
            'packages' => [],
        ], $dest);

        $array = $plan->toArray();
        self::assertArrayHasKey('operations', $array);
        self::assertArrayHasKey('destination', $array);
        self::assertNotEmpty($array['operations']);
    }
}
