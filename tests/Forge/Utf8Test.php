<?php

declare(strict_types=1);

namespace Tests\Forge;

use App\Forge\Support\Utf8;
use PHPUnit\Framework\TestCase;

final class Utf8Test extends TestCase
{
    public function testPassesThroughValidUtf8(): void
    {
        self::assertSame('déjà', Utf8::stringify('déjà'));
    }

    public function testConvertsCp1252(): void
    {
        // "échoué" in CP1252
        $cp1252 = "\xe9chou\xe9";
        $out = Utf8::stringify($cp1252);
        self::assertTrue(mb_check_encoding($out, 'UTF-8'));
        self::assertStringContainsString('chou', $out);
    }

    public function testDeepArray(): void
    {
        $data = ['error' => "\xe9chec", 'nested' => ['m' => "ok"]];
        /** @var array{error: string, nested: array{m: string}} $safe */
        $safe = Utf8::deep($data);
        self::assertTrue(mb_check_encoding($safe['error'], 'UTF-8'));
        json_encode($safe, JSON_THROW_ON_ERROR);
        self::assertSame('ok', $safe['nested']['m']);
    }
}
