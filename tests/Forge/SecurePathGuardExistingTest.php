<?php

declare(strict_types=1);

namespace Tests\Forge;

use App\Forge\Exception\UnsafePathException;
use App\Forge\Security\SecurePathGuard;
use PHPUnit\Framework\TestCase;

final class SecurePathGuardExistingTest extends TestCase
{
    private string $root = '';

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'forge-path-' . bin2hex(random_bytes(4));
        mkdir($this->root . DIRECTORY_SEPARATOR . 'app', 0777, true);
    }

    protected function tearDown(): void
    {
        $app = $this->root . DIRECTORY_SEPARATOR . 'app';
        if (is_dir($app)) {
            rmdir($app);
        }
        if (is_dir($this->root)) {
            rmdir($this->root);
        }
    }

    public function testResolveExistingDirectory(): void
    {
        $guard = new SecurePathGuard([$this->root], $this->root);
        $resolved = $guard->resolveExistingDirectory($this->root . DIRECTORY_SEPARATOR . 'app');
        self::assertStringEndsWith('/app', str_replace('\\', '/', $resolved));
    }

    public function testMissingDirectoryFails(): void
    {
        $guard = new SecurePathGuard([$this->root], $this->root);
        $this->expectException(UnsafePathException::class);
        $guard->resolveExistingDirectory($this->root . DIRECTORY_SEPARATOR . 'missing');
    }
}
