<?php

declare(strict_types=1);

namespace Tests\Forge;

use App\Forge\Blueprint\BlueprintValidator;
use App\Forge\Plan\GenerationPlanner;
use App\Forge\Plan\PlanOperation;
use PHPUnit\Framework\TestCase;

final class GenerationPlannerTest extends TestCase
{
    public function testPlanIsDeterministic(): void
    {
        $validator = new BlueprintValidator();
        $planner = new GenerationPlanner('astral-php/astral-starter:^2.1');
        $blueprint = $validator->validate([
            'schemaVersion' => 1,
            'project' => ['name' => 'demo-app', 'target' => 'astral-starter-2.1'],
            'recipe' => 'blank',
            'features' => ['authentication' => true, 'admin_users' => true, 'api' => false],
            'views' => ['engine' => 'php'],
            'packages' => [],
        ]);

        $planA = $planner->plan($blueprint, 'D:/tmp/demo-app');
        $planB = $planner->plan($blueprint, 'D:/tmp/demo-app');

        self::assertSame($planA->toArray(), $planB->toArray());
        self::assertCount(3, $planA->operations);
        self::assertSame(PlanOperation::TYPE_ENSURE_EMPTY_DIR, $planA->operations[0]->type);
        self::assertSame(PlanOperation::TYPE_COMPOSER_CREATE_PROJECT, $planA->operations[1]->type);
        self::assertSame('demo-app', $planA->operations[1]->meta['project_name']);
    }
}
