<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Forge\Install\EnvGenerator;
use PHPUnit\Framework\TestCase;

final class EnvGeneratorTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = sys_get_temp_dir() . '/forge-env-' . bin2hex(random_bytes(4));
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $env = $this->tmpDir . '/.env';
        if (is_file($env)) {
            unlink($env);
        }
        if (is_dir($this->tmpDir)) {
            rmdir($this->tmpDir);
        }
        parent::tearDown();
    }

    public function testGenerateWritesProductionEnv(): void
    {
        $gen = new EnvGenerator($this->tmpDir, windows: false);
        $result = $gen->generate();

        self::assertTrue($result['written']);
        self::assertFileExists($this->tmpDir . '/.env');
        $contents = (string) file_get_contents($this->tmpDir . '/.env');
        self::assertStringContainsString('APP_ENV=production', $contents);
        self::assertStringContainsString('APP_VERSION=0.6.1', $contents);
        self::assertStringContainsString('FORGE_ALLOWED_ROOT=', $contents);
        self::assertSame(dirname($this->tmpDir), $result['allowed_root']);
    }

    public function testGenerateSkipsExistingWithoutForce(): void
    {
        file_put_contents($this->tmpDir . '/.env', "APP_ENV=development\n");
        $gen = new EnvGenerator($this->tmpDir, windows: false);
        $result = $gen->generate(false);

        self::assertTrue($result['skipped']);
        self::assertStringContainsString('APP_ENV=development', (string) file_get_contents($this->tmpDir . '/.env'));
    }

    public function testGenerateForceOverwrites(): void
    {
        file_put_contents($this->tmpDir . '/.env', "APP_ENV=development\n");
        $gen = new EnvGenerator($this->tmpDir, windows: false);
        $result = $gen->generate(true);

        self::assertTrue($result['written']);
        self::assertStringContainsString('APP_ENV=production', (string) file_get_contents($this->tmpDir . '/.env'));
    }
}
