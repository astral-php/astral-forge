<?php

declare(strict_types=1);

namespace Tests\Forge;

use App\Forge\Atelier\ProjectMigrationInspector;
use App\Models\ForgeProject;
use Database\Migration\Migrator;
use PDO;
use PHPUnit\Framework\TestCase;

final class ProjectMigrationInspectorTest extends TestCase
{
    private string $root = '';

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'forge-mig-' . bin2hex(random_bytes(4));
        mkdir($this->root . '/database/migrations', 0777, true);
        file_put_contents($this->root . '/.env', "DB_DRIVER=sqlite\nDB_DATABASE=database/app.sqlite\n");
    }

    protected function tearDown(): void
    {
        $this->rmTree($this->root);
    }

    public function testReportsPendingThenUpToDate(): void
    {
        $file = '2026_11_07_000001_create_demo_table.php';
        file_put_contents(
            $this->root . '/database/migrations/' . $file,
            <<<'PHP'
<?php
declare(strict_types=1);
use Database\Migration\Migration;
class CreateDemoTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $pdo->exec('CREATE TABLE IF NOT EXISTS demo (id INTEGER PRIMARY KEY)');
    }
    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS demo');
    }
}
PHP
        );

        $project = new ForgeProject();
        $project->id = 1;
        $project->name = 'demo';
        $project->path = $this->root;
        $project->recipe = 'blank';

        $inspector = new ProjectMigrationInspector();
        $before = $inspector->inspect($project);
        self::assertTrue($before['ok']);
        self::assertSame('pending', $before['badge']);
        self::assertSame(1, $before['pending_count']);

        $pdo = new PDO('sqlite:' . $this->root . '/database/app.sqlite');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $migrator = new Migrator($pdo, $this->root . '/database/migrations');
        $migrator->run();

        $after = $inspector->inspect($project);
        self::assertSame('up_to_date', $after['badge']);
        self::assertSame(0, $after['pending_count']);
        self::assertSame(1, $after['applied_count']);
    }

    private function rmTree(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->rmTree($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
