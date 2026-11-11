<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Forge\Install\InstallGate;
use PDO;
use PHPUnit\Framework\TestCase;

final class InstallGateTest extends TestCase
{
    private string $tmpDir;

    private string $migrationsDir;

    private string $migrationFile;

    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $suffix = bin2hex(random_bytes(3));
        $this->tmpDir = sys_get_temp_dir() . '/forge-install-' . $suffix;
        $this->migrationsDir = $this->tmpDir . '/database/migrations';
        mkdir($this->migrationsDir, 0777, true);
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Classe unique (Migrator require_once plusieurs chemins sinon collision globale)
        $class = 'CreateDemo' . ucfirst($suffix) . 'Table';
        $stamp = '2026_11_11_000001_create_demo_' . $suffix . '_table.php';
        $this->migrationFile = $this->migrationsDir . '/' . $stamp;
        file_put_contents($this->migrationFile, <<<PHP
<?php
declare(strict_types=1);
use Database\\Migration\\Migration;
class {$class} extends Migration
{
    public function up(PDO \$pdo): void
    {
        \$pdo->exec('CREATE TABLE IF NOT EXISTS demo_{$suffix} (id INTEGER PRIMARY KEY)');
    }
    public function down(PDO \$pdo): void
    {
        \$pdo->exec('DROP TABLE IF EXISTS demo_{$suffix}');
    }
}
PHP);
    }

    protected function tearDown(): void
    {
        if (isset($this->migrationFile) && is_file($this->migrationFile)) {
            unlink($this->migrationFile);
        }
        if (is_dir($this->migrationsDir)) {
            rmdir($this->migrationsDir);
        }
        $dbDir = $this->tmpDir . '/database';
        if (is_dir($dbDir)) {
            rmdir($dbDir);
        }
        if (is_dir($this->tmpDir)) {
            rmdir($this->tmpDir);
        }
        parent::tearDown();
    }

    public function testNeedsMigrateThenResolved(): void
    {
        $gate = new InstallGate($this->pdo, $this->migrationsDir);
        self::assertTrue($gate->needsMigrate());

        $executed = $gate->migrate();
        self::assertCount(1, $executed);
        self::assertFalse($gate->needsMigrate());
        self::assertSame(0, $gate->summary()['pending_count']);
        self::assertSame(1, $gate->summary()['applied_count']);
    }
}
