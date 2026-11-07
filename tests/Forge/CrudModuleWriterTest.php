<?php

declare(strict_types=1);

namespace Tests\Forge;

use App\Forge\Generator\ApiBootstrapWriter;
use App\Forge\Generator\CrudModuleWriter;
use PHPUnit\Framework\TestCase;

final class CrudModuleWriterTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'forge-crud-' . bin2hex(random_bytes(4));
        mkdir($this->root . '/app/Models', 0755, true);
        mkdir($this->root . '/app/Dao', 0755, true);
        mkdir($this->root . '/app/Controllers', 0755, true);
        mkdir($this->root . '/app/Providers', 0755, true);
        mkdir($this->root . '/config', 0755, true);
        mkdir($this->root . '/database/migrations', 0755, true);
        mkdir($this->root . '/views', 0755, true);

        file_put_contents($this->root . '/config/routes.php', <<<'PHP'
<?php

declare(strict_types=1);

use Core\Router;

return function (Router $router): void {
    $router->get('/', \App\Controllers\HomeController::class, 'index');
};
PHP);

        file_put_contents($this->root . '/config/dependencies.php', <<<'PHP'
<?php

declare(strict_types=1);

return [
    App\Providers\AppServiceProvider::class,
];
PHP);
    }

    protected function tearDown(): void
    {
        $this->rmTree($this->root);
    }

    public function testWritesArticleModuleFiles(): void
    {
        $writer = new CrudModuleWriter();
        $files = $writer->write($this->root, 'Article', publicList: true);

        self::assertNotEmpty($files);
        self::assertFileExists($this->root . '/app/Models/Article.php');
        self::assertFileExists($this->root . '/app/Dao/ArticleDao.php');
        self::assertFileExists($this->root . '/app/Controllers/ArticleController.php');
        self::assertFileExists($this->root . '/views/article/index.php');
        self::assertFileExists($this->root . '/config/forge_routes.php');
        self::assertFileExists($this->root . '/app/Providers/ForgeGeneratedServiceProvider.php');

        $routes = (string) file_get_contents($this->root . '/config/routes.php');
        self::assertStringContainsString('forge_routes.php', $routes);

        $deps = (string) file_get_contents($this->root . '/config/dependencies.php');
        self::assertStringContainsString('ForgeGeneratedServiceProvider', $deps);

        $controller = (string) file_get_contents($this->root . '/app/Controllers/ArticleController.php');
        self::assertStringContainsString('RedirectResponse::to(', $controller);
        self::assertStringNotContainsString('new RedirectResponse(', $controller);

        $migrations = glob($this->root . '/database/migrations/*_create_articles_table.php') ?: [];
        self::assertCount(1, $migrations);
        $migration = (string) file_get_contents($migrations[0]);
        self::assertStringContainsString('class CreateArticlesTable extends Migration', $migration);
        self::assertStringContainsString('use Database\\Migration\\Migration;', $migration);
        self::assertStringNotContainsString('MigrationInterface', $migration);
        self::assertStringNotContainsString('return new class', $migration);

        $lint = [];
        $code = 0;
        exec('php -l ' . escapeshellarg($migrations[0]) . ' 2>&1', $lint, $code);
        self::assertSame(0, $code, implode("\n", $lint));
    }

    public function testWritesApiBootstrap(): void
    {
        $writer = new ApiBootstrapWriter();
        $files = $writer->write($this->root);

        self::assertNotEmpty($files);
        self::assertFileExists($this->root . '/app/Controllers/Api/HealthController.php');
        $forgeRoutes = (string) file_get_contents($this->root . '/config/forge_routes.php');
        self::assertStringContainsString('/api/v1', $forgeRoutes);
        self::assertStringContainsString('HealthController', $forgeRoutes);
    }

    private function rmTree(string $dir): void
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
                $this->rmTree($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
