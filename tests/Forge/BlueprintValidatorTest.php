<?php

declare(strict_types=1);

namespace Tests\Forge;

use App\Forge\Blueprint\BlueprintValidator;
use App\Forge\Exception\InvalidBlueprintException;
use PHPUnit\Framework\TestCase;

final class BlueprintValidatorTest extends TestCase
{
    private BlueprintValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new BlueprintValidator();
    }

    public function testValidBlankBlueprint(): void
    {
        $bp = $this->validator->validate($this->validData());

        self::assertSame(1, $bp->schemaVersion);
        self::assertSame('mon-application', $bp->projectName);
        self::assertSame('blank', $bp->recipe);
        self::assertSame([], $bp->modules);
    }

    public function testBlogLiteImpliesArticleModule(): void
    {
        $data = $this->validData();
        $data['recipe'] = 'blog-lite';
        $data['features']['crud_resource'] = true;
        unset($data['modules']);

        $bp = $this->validator->validate($data);

        self::assertSame(['Article'], $bp->modules);
        self::assertTrue($bp->features['crud_resource']);
    }

    public function testApiJsonRequiresApiFlag(): void
    {
        $data = $this->validData();
        $data['recipe'] = 'api-json';
        $data['features']['api'] = true;

        $bp = $this->validator->validate($data);

        self::assertTrue($bp->features['api']);
    }

    public function testRejectsUnknownKey(): void
    {
        $this->expectException(InvalidBlueprintException::class);
        $data = $this->validData();
        $data['evil'] = true;
        $this->validator->validate($data);
    }

    public function testRejectsBadName(): void
    {
        $this->expectException(InvalidBlueprintException::class);
        $data = $this->validData();
        $data['project']['name'] = 'Bad Name';
        $this->validator->validate($data);
    }

    public function testRejectsApiOnBlank(): void
    {
        $this->expectException(InvalidBlueprintException::class);
        $data = $this->validData();
        $data['features']['api'] = true;
        $this->validator->validate($data);
    }

    public function testRejectsTwide(): void
    {
        $this->expectException(InvalidBlueprintException::class);
        $data = $this->validData();
        $data['views']['engine'] = 'twide';
        $this->validator->validate($data);
    }

    /**
     * @return array<string, mixed>
     */
    private function validData(): array
    {
        return [
            'schemaVersion' => 1,
            'project' => [
                'name' => 'mon-application',
                'target' => 'astral-starter-2.1',
            ],
            'recipe' => 'blank',
            'features' => [
                'authentication' => true,
                'admin_users' => true,
                'api' => false,
                'crud_resource' => false,
            ],
            'views' => ['engine' => 'php'],
            'packages' => [],
        ];
    }
}
