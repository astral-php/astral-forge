<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Forge\Runtime\RuntimeHealth;
use PHPUnit\Framework\TestCase;

final class RuntimeHealthTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = sys_get_temp_dir() . '/forge-health-' . bin2hex(random_bytes(4));
        mkdir($this->tmpDir . '/storage', 0777, true);
        mkdir($this->tmpDir . '/writable-root', 0777, true);
    }

    protected function tearDown(): void
    {
        $home = $this->tmpDir . '/storage/composer-home';
        if (is_dir($home)) {
            @rmdir($home);
        }
        @rmdir($this->tmpDir . '/storage');
        @rmdir($this->tmpDir . '/writable-root');
        @rmdir($this->tmpDir);
        parent::tearDown();
    }

    public function testComposerHomeFallsBackToStorageWhenNoEnv(): void
    {
        $prevHome = getenv('COMPOSER_HOME');
        $prevAppData = getenv('APPDATA');
        $prevUser = getenv('USERPROFILE');
        $prevUnixHome = getenv('HOME');
        putenv('COMPOSER_HOME');
        putenv('APPDATA');
        putenv('USERPROFILE');
        putenv('HOME');
        try {
            $health = new RuntimeHealth($this->tmpDir, [$this->tmpDir . '/writable-root']);
            $status = $health->composerHomeStatus();
            self::assertTrue($status['writable']);
            self::assertStringContainsString('composer-home', $status['path']);
            self::assertContains($status['source'], ['storage', 'temp']);
        } finally {
            putenv(is_string($prevHome) && $prevHome !== '' ? 'COMPOSER_HOME=' . $prevHome : 'COMPOSER_HOME');
            putenv(is_string($prevAppData) && $prevAppData !== '' ? 'APPDATA=' . $prevAppData : 'APPDATA');
            putenv(is_string($prevUser) && $prevUser !== '' ? 'USERPROFILE=' . $prevUser : 'USERPROFILE');
            putenv(is_string($prevUnixHome) && $prevUnixHome !== '' ? 'HOME=' . $prevUnixHome : 'HOME');
        }
    }

    public function testPermissionHintAndDetection(): void
    {
        $health = new RuntimeHealth($this->tmpDir, [$this->tmpDir . '/writable-root']);
        self::assertTrue($health->looksLikePermissionError('mkdir(): Permission denied'));
        self::assertStringContainsString('www-data', $health->permissionDeniedHint($this->tmpDir . '/app'));
        $roots = $health->allowedRootsStatus();
        self::assertTrue($roots[0]['writable']);
    }
}
