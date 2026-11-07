<?php

declare(strict_types=1);

namespace Tests\Forge;

use App\Forge\Atelier\ProjectDirectoryRemover;
use App\Forge\Exception\UnsafePathException;
use App\Forge\Security\SecurePathGuard;
use PHPUnit\Framework\TestCase;

final class ProjectDirectoryRemoverTest extends TestCase
{
    private string $root = '';

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'forge-rm-' . bin2hex(random_bytes(4));
        mkdir($this->root . DIRECTORY_SEPARATOR . 'demo' . DIRECTORY_SEPARATOR . 'nested', 0777, true);
        file_put_contents($this->root . '/demo/nested/file.txt', 'x');
    }

    protected function tearDown(): void
    {
        $this->rmTree($this->root);
    }

    public function testDeletesProjectTree(): void
    {
        $guard = new SecurePathGuard([$this->root], $this->root);
        $remover = new ProjectDirectoryRemover($guard);
        $target = $this->root . DIRECTORY_SEPARATOR . 'demo';

        $remover->delete($target);

        self::assertDirectoryDoesNotExist($target);
        self::assertDirectoryExists($this->root);
    }

    public function testRefusesDeletingAllowedRoot(): void
    {
        $guard = new SecurePathGuard([$this->root], $this->root);
        $remover = new ProjectDirectoryRemover($guard);

        $this->expectException(UnsafePathException::class);
        $remover->delete($this->root);
    }

    private function rmTree(string $path): void
    {
        if (!is_dir($path) && !is_file($path)) {
            return;
        }
        if (is_file($path) || is_link($path)) {
            @unlink($path);

            return;
        }
        foreach (scandir($path) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $this->rmTree($path . DIRECTORY_SEPARATOR . $item);
        }
        @rmdir($path);
    }
}
