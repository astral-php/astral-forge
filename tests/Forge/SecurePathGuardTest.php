<?php

declare(strict_types=1);

namespace Tests\Forge;

use App\Forge\Exception\UnsafePathException;
use App\Forge\Security\SecurePathGuard;
use PHPUnit\Framework\TestCase;

final class SecurePathGuardTest extends TestCase
{
    public function testAllowsPathUnderTempRoot(): void
    {
        $root = sys_get_temp_dir();
        $guard = new SecurePathGuard([$root], dirname(__DIR__, 2));
        $destName = 'forge-path-test-' . bin2hex(random_bytes(4));
        $resolved = $guard->resolveDestination($root . DIRECTORY_SEPARATOR . $destName);

        self::assertStringContainsString($destName, $resolved);
    }

    public function testRejectsPathOutsideAllowedRoots(): void
    {
        $this->expectException(UnsafePathException::class);
        // Racine autorisée = temp uniquement ; sortir via chemin absolu ailleurs
        $guard = new SecurePathGuard([sys_get_temp_dir()], dirname(__DIR__, 2));
        $outside = dirname(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'forge-outside-' . bin2hex(random_bytes(3));
        $guard->resolveDestination($outside);
    }

    public function testAllowsRelativeDotDotWhenStillInsideRoot(): void
    {
        $root = sys_get_temp_dir();
        $subdir = $root . DIRECTORY_SEPARATOR . 'forge-sub-' . bin2hex(random_bytes(3));
        mkdir($subdir);
        try {
            $guard = new SecurePathGuard([$root], $subdir);
            $name = 'sibling-' . bin2hex(random_bytes(3));
            $resolved = $guard->resolveDestination('..' . DIRECTORY_SEPARATOR . $name);
            self::assertStringEndsWith('/' . $name, str_replace('\\', '/', $resolved));
        } finally {
            @rmdir($subdir);
        }
    }
}
