<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Forge\Exception\GenerationException;
use App\Forge\Generator\EntityNaming;
use PHPUnit\Framework\TestCase;

final class EntityNamingTest extends TestCase
{
    public function testPluralTableFromSingular(): void
    {
        self::assertSame('categories', EntityNaming::toSnakePlural('Category'));
        self::assertSame('tasks', EntityNaming::toSnakePlural('Task'));
        self::assertSame('articles', EntityNaming::toSnakePlural('Article'));
        self::assertSame('news', EntityNaming::toSnakePlural('News'));
        self::assertSame('people', EntityNaming::toSnakePlural('Person'));
    }

    public function testAssertRejectsPluralAndNonAscii(): void
    {
        $this->expectException(GenerationException::class);
        EntityNaming::assertValidEntity('Categories');
    }

    public function testAssertRejectsAccent(): void
    {
        $this->expectException(GenerationException::class);
        EntityNaming::assertValidEntity('Catégorie');
    }

    public function testAssertAcceptsSingular(): void
    {
        self::assertSame('Category', EntityNaming::assertValidEntity('Category'));
        self::assertSame('News', EntityNaming::assertValidEntity('News'));
        self::assertSame('Status', EntityNaming::assertValidEntity('Status'));
    }

    public function testPreviewSuggestsSingular(): void
    {
        $preview = EntityNaming::preview('Categories');
        self::assertTrue($preview['looks_plural']);
        self::assertSame('Category', $preview['suggested_entity']);
        self::assertFalse($preview['ok']);
    }
}
