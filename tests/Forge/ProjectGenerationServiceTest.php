<?php

declare(strict_types=1);

namespace Tests\Forge;

use App\Forge\Blueprint\BlueprintValidator;
use App\Forge\Exception\GenerationException;
use App\Forge\Plan\GenerationPlanner;
use App\Forge\ProjectGenerationService;
use App\Forge\Resolver\DependencyResolver;
use App\Forge\Security\SecurePathGuard;
use App\Forge\Writer\ComposerCreateProjectRunner;
use App\Forge\Writer\PlanExecutor;
use PHPUnit\Framework\TestCase;

final class ProjectGenerationServiceTest extends TestCase
{
    public function testDryRunDoesNotWrite(): void
    {
        $root = sys_get_temp_dir();
        $dest = $root . DIRECTORY_SEPARATOR . 'forge-dry-' . bin2hex(random_bytes(4));
        $service = $this->service([$root]);

        $report = $service->generate($this->blueprint(), $dest, confirm: true, dryRun: true);

        self::assertTrue($report->success);
        self::assertTrue($report->dryRun);
        self::assertDirectoryDoesNotExist($dest);
    }

    public function testGenerateRequiresConfirm(): void
    {
        $this->expectException(GenerationException::class);
        $root = sys_get_temp_dir();
        $dest = $root . DIRECTORY_SEPARATOR . 'forge-noconfirm-' . bin2hex(random_bytes(4));
        $this->service([$root])->generate($this->blueprint(), $dest, confirm: false, dryRun: false);
    }

    /**
     * @param list<string> $roots
     */
    private function service(array $roots): ProjectGenerationService
    {
        return new ProjectGenerationService(
            new BlueprintValidator(),
            new DependencyResolver(),
            new GenerationPlanner(),
            new SecurePathGuard($roots, dirname(__DIR__, 2)),
            new PlanExecutor(new ComposerCreateProjectRunner()),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function blueprint(): array
    {
        return [
            'schemaVersion' => 1,
            'project' => ['name' => 'demo-forge', 'target' => 'astral-starter-2.1'],
            'recipe' => 'blank',
            'features' => ['authentication' => true, 'admin_users' => true, 'api' => false],
            'views' => ['engine' => 'php'],
            'packages' => [],
        ];
    }
}
