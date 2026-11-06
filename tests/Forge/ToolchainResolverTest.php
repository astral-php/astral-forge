<?php

declare(strict_types=1);

namespace Tests\Forge;

use App\Forge\Exception\GenerationException;
use App\Forge\Runtime\ToolchainResolver;
use PHPUnit\Framework\TestCase;

final class ToolchainResolverTest extends TestCase
{
    private string $tmp = '';

    protected function setUp(): void
    {
        $this->tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'forge-tc-' . bin2hex(random_bytes(4));
        mkdir($this->tmp . DIRECTORY_SEPARATOR . 'php', 0777, true);
        mkdir($this->tmp . DIRECTORY_SEPARATOR . 'composer', 0777, true);
        file_put_contents($this->tmp . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'php.exe', '');
        file_put_contents($this->tmp . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'composer.phar', '');
        file_put_contents($this->tmp . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'composer.bat', "@ECHO OFF\r\n");
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->tmp);
    }

    public function testPrefersPharWithConfiguredPhp(): void
    {
        $php = $this->tmp . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'php.exe';
        $phar = $this->tmp . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'composer.phar';
        $resolver = new ToolchainResolver($php, $phar, windows: true);

        $resolved = $resolver->resolve();

        self::assertSame($php, $resolved->phpBin);
        self::assertSame([$php, $phar], $resolved->composerPrefix);
        self::assertTrue($resolved->bypassShell);
    }

    public function testBatShimResolvesToSiblingPhar(): void
    {
        $php = $this->tmp . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'php.exe';
        $bat = $this->tmp . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'composer.bat';
        $phar = $this->tmp . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'composer.phar';
        $resolver = new ToolchainResolver($php, $bat, windows: true);

        $resolved = $resolver->resolve();

        self::assertSame([$php, $phar], $resolved->composerPrefix);
        self::assertTrue($resolved->bypassShell);
    }

    public function testLaragonLayoutFromPhpBinaryNeighbor(): void
    {
        // .../bin/php/php-8.3.x/php.exe → .../bin/composer/composer.phar
        $bin = $this->tmp . DIRECTORY_SEPARATOR . 'bin';
        $phpDir = $bin . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'php-8.3.x';
        $composerDir = $bin . DIRECTORY_SEPARATOR . 'composer';
        mkdir($phpDir, 0777, true);
        mkdir($composerDir, 0777, true);
        $php = $phpDir . DIRECTORY_SEPARATOR . 'php.exe';
        $phar = $composerDir . DIRECTORY_SEPARATOR . 'composer.phar';
        file_put_contents($php, '');
        file_put_contents($phar, '');

        // Pas de composer_bin → guess Laragon (PATH vide simulé via windows + config php only)
        $resolver = new ToolchainResolver($php, '', windows: true);
        // Vider PATH pour forcer le fallback Laragon
        $prev = getenv('PATH');
        putenv('PATH=');
        try {
            $resolved = $resolver->resolve();
        } finally {
            putenv('PATH=' . ($prev === false ? '' : $prev));
        }

        self::assertSame([$php, $phar], $resolved->composerPrefix);
    }

    public function testPreflightOkWhenBinsExist(): void
    {
        $php = $this->tmp . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'php.exe';
        $phar = $this->tmp . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'composer.phar';
        $status = (new ToolchainResolver($php, $phar, windows: true))->preflight();

        self::assertTrue($status['ok']);
        self::assertTrue($status['proc_open']);
        self::assertSame([], $status['errors']);
    }

    public function testMissingComposerThrows(): void
    {
        $isolated = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'forge-tc-miss-' . bin2hex(random_bytes(4));
        mkdir($isolated, 0777, true);
        $php = $isolated . DIRECTORY_SEPARATOR . 'php.exe';
        file_put_contents($php, '');

        $resolver = new ToolchainResolver($php, '', windows: true);
        $prev = getenv('PATH');
        putenv('PATH=');
        try {
            $this->expectException(GenerationException::class);
            $resolver->resolve();
        } finally {
            putenv('PATH=' . ($prev === false ? '' : $prev));
            @unlink($php);
            @rmdir($isolated);
        }
    }

    public function testRejectsHttpdAsPhpBinaryAndFindsLaragonPhp(): void
    {
        $root = $this->tmp . DIRECTORY_SEPARATOR . 'laragon';
        $httpdDir = $root . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'apache'
            . DIRECTORY_SEPARATOR . 'httpd-2.4' . DIRECTORY_SEPARATOR . 'bin';
        $phpDir = $root . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php'
            . DIRECTORY_SEPARATOR . 'php-8.3.33-Win32-vs16-x64';
        mkdir($httpdDir, 0777, true);
        mkdir($phpDir, 0777, true);
        $httpd = $httpdDir . DIRECTORY_SEPARATOR . 'httpd.exe';
        $php = $phpDir . DIRECTORY_SEPARATOR . 'php.exe';
        file_put_contents($httpd, '');
        file_put_contents($php, '');

        $resolver = new ToolchainResolver('', '', windows: true);
        $usable = new \ReflectionMethod($resolver, 'isUsablePhpCliBinary');
        self::assertFalse($usable->invoke($resolver, $httpd));
        self::assertTrue($usable->invoke($resolver, $php));

        $guess = new \ReflectionMethod($resolver, 'guessLaragonPhpNearApache');
        self::assertSame($php, $guess->invoke($resolver, $httpd));
    }

    private function removeTree(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeTree($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
