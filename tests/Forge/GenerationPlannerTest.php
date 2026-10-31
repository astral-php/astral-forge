<?php

declare(strict_types=1);

namespace Tests\Forge;

use App\Forge\Blueprint\BlueprintValidator;
use App\Forge\Plan\GenerationPlanner;
use App\Forge\Plan\PlanOperation;
use PHPUnit\Framework\TestCase;

final class GenerationPlannerTest extends TestCase
{
    public function testBlankPlanIsDeterministic(): void
    {
        $planA = $this->plan('blank', ['api' => false, 'crud_resource' => false]);
        $planB = $this->plan('blank', ['api' => false, 'crud_resource' => false]);

        self::assertSame($planA->toArray(), $planB->toArray());
        self::assertSame(PlanOperation::TYPE_COMPOSER_CREATE_PROJECT, $planA->operations[1]->type);
    }

    public function testBlogLiteIncludesCrudOperation(): void
    {
        $plan = $this->plan('blog-lite', ['api' => false, 'crud_resource' => true]);
        $types = array_map(static fn ($op) => $op->type, $plan->operations);

        self::assertContains(PlanOperation::TYPE_WRITE_CRUD_MODULE, $types);
        self::assertSame('Article', $plan->operations[2]->meta['entity']);
    }

    public function testApiJsonIncludesApiBootstrap(): void
    {
        $plan = $this->plan('api-json', ['api' => true, 'crud_resource' => false]);
        $types = array_map(static fn ($op) => $op->type, $plan->operations);

        self::assertContains(PlanOperation::TYPE_WRITE_API_BOOTSTRAP, $types);
    }

    /**
     * @param array{api: bool, crud_resource: bool} $features
     */
    private function plan(string $recipe, array $features): \App\Forge\Plan\GenerationPlan
    {
        $validator = new BlueprintValidator();
        $planner = new GenerationPlanner('astral-php/astral-starter:^2.1');
        $blueprint = $validator->validate([
            'schemaVersion' => 1,
            'project' => ['name' => 'demo-app', 'target' => 'astral-starter-2.1'],
            'recipe' => $recipe,
            'features' => [
                'authentication' => true,
                'admin_users' => true,
                'api' => $features['api'],
                'crud_resource' => $features['crud_resource'],
            ],
            'views' => ['engine' => 'php'],
            'packages' => [],
        ]);

        return $planner->plan($blueprint, 'D:/tmp/demo-app');
    }
}
