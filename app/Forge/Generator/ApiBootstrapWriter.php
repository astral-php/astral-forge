<?php

declare(strict_types=1);

namespace App\Forge\Generator;

use App\Forge\Exception\GenerationException;

/**
 * Bootstrap API JSON (/api/v1) + Cors sur un projet starter.
 */
final class ApiBootstrapWriter
{
    /**
     * @return list<string>
     */
    public function write(string $projectRoot, string $prefix = '/api/v1'): array
    {
        if (!is_dir($projectRoot)) {
            throw new GenerationException('Projet introuvable : ' . $projectRoot);
        }

        $written = [];
        $controllerPath = $projectRoot . '/app/Controllers/Api/HealthController.php';
        if (!is_dir(dirname($controllerPath))) {
            mkdir(dirname($controllerPath), 0755, true);
        }
        if (is_file($controllerPath)) {
            throw new GenerationException('Collision : ' . $controllerPath);
        }

        $controller = <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use Controller\AbstractApiController;
use Core\Http\JsonResponse;

final class HealthController extends AbstractApiController
{
    public function index(): JsonResponse
    {
        return $this->json([
            'ok' => true,
            'service' => 'api',
            'version' => 'v1',
        ]);
    }
}

PHP;
        file_put_contents($controllerPath, $controller);
        $written[] = str_replace('\\', '/', $controllerPath);

        $this->ensureRoutesHook($projectRoot);
        $this->appendApiRoutes($projectRoot, $prefix);
        $this->ensureProvider($projectRoot);

        return $written;
    }

    private function ensureRoutesHook(string $projectRoot): void
    {
        $routes = $projectRoot . '/config/routes.php';
        if (!is_file($routes)) {
            throw new GenerationException('config/routes.php introuvable.');
        }
        $contents = file_get_contents($routes);
        if ($contents === false) {
            throw new GenerationException('Lecture routes.php impossible.');
        }
        if (str_contains($contents, 'forge_routes.php')) {
            return;
        }
        $hook = <<<'PHP'

    $forgeRoutes = dirname(__DIR__) . '/config/forge_routes.php';
    if (is_file($forgeRoutes)) {
        (require $forgeRoutes)($router);
    }
PHP;
        $patched = preg_replace('/\};\s*$/', $hook . "\n};\n", $contents, 1);
        if ($patched === null || $patched === $contents) {
            throw new GenerationException('Hook forge_routes impossible.');
        }
        file_put_contents($routes, $patched);
    }

    private function appendApiRoutes(string $projectRoot, string $prefix): void
    {
        $path = $projectRoot . '/config/forge_routes.php';
        if (!is_file($path)) {
            file_put_contents($path, <<<'PHP'
<?php

declare(strict_types=1);

use Core\Auth\Middleware\AuthMiddleware;
use Core\Middleware\CorsMiddleware;
use Core\Router;

return function (Router $router): void {
};
PHP);
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new GenerationException('Lecture forge_routes.php impossible.');
        }
        if (!str_contains($contents, 'CorsMiddleware')) {
            $contents = str_replace(
                'use Core\Router;',
                "use Core\\Middleware\\CorsMiddleware;\nuse Core\\Router;",
                $contents
            );
        }
        if (str_contains($contents, 'HealthController::class')) {
            file_put_contents($path, $contents);

            return;
        }

        $block = <<<PHP

    \$router->group('{$prefix}', function (Router \$r): void {
        \$r->get('/health', \\App\\Controllers\\Api\\HealthController::class, 'index');
    }, [CorsMiddleware::class]);

PHP;
        $patched = preg_replace('/\};\s*$/', $block . "};\n", $contents, 1);
        if ($patched === null) {
            throw new GenerationException('Patch API routes échoué.');
        }
        file_put_contents($path, $patched);
    }

    private function ensureProvider(string $projectRoot): void
    {
        $path = $projectRoot . '/app/Providers/ForgeGeneratedServiceProvider.php';
        if (!is_file($path)) {
            file_put_contents($path, <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Providers;

use Core\Container;
use Core\ServiceProviderInterface;

final class ForgeGeneratedServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container, array $appConfig, array $dbConfig): void
    {
        // @forge-bindings
        $container->bind(\App\Controllers\Api\HealthController::class, static fn () => new \App\Controllers\Api\HealthController());
    }
}

PHP);
        } else {
            $contents = file_get_contents($path);
            if ($contents !== false && !str_contains($contents, 'HealthController::class')) {
                $contents = str_replace(
                    '// @forge-bindings',
                    "// @forge-bindings\n        \$container->bind(\\App\\Controllers\\Api\\HealthController::class, static fn () => new \\App\\Controllers\\Api\\HealthController());\n",
                    $contents
                );
                file_put_contents($path, $contents);
            }
        }

        $deps = $projectRoot . '/config/dependencies.php';
        $depContents = file_get_contents($deps);
        if ($depContents === false) {
            throw new GenerationException('Lecture dependencies.php impossible.');
        }
        if (!str_contains($depContents, 'ForgeGeneratedServiceProvider')) {
            $depContents = str_replace(
                'AppServiceProvider::class,',
                "AppServiceProvider::class,\n    \\App\\Providers\\ForgeGeneratedServiceProvider::class,",
                $depContents
            );
            file_put_contents($deps, $depContents);
        }
    }
}
