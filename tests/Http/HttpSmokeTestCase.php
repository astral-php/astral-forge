<?php

declare(strict_types=1);

namespace Tests\Http;

use Core\Auth\Auth;
use Core\Container;
use Core\CsrfGuard;
use Core\Http\Response;
use Core\Request;
use Core\Router;
use Core\Session;
use Core\View;
use Database\Connection;
use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;

/**
 * Bootstrap léger pour smokes HTTP (Router::handle → Response, sans send()).
 * Requiert astral-core ^2.0.
 */
abstract class HttpSmokeTestCase extends TestCase
{
    protected string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = dirname(__DIR__, 2);

        if (!defined('BASE_PATH')) {
            define('BASE_PATH', $this->basePath);
        }

        foreach (['storage/logs', 'storage/cache', 'database'] as $dir) {
            $path = $this->basePath . '/' . $dir;
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }

        Connection::reset();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        Connection::reset();
        parent::tearDown();
    }

    protected function get(string $uri): Response
    {
        return $this->request('GET', $uri);
    }

    protected function request(string $method, string $uri): Response
    {
        Connection::reset();

        $_SERVER['REQUEST_METHOD'] = strtoupper($method);
        $_SERVER['REQUEST_URI']    = $uri;
        $_GET  = [];
        $_POST = [];

        Dotenv::createImmutable($this->basePath)->safeLoad();

        $_ENV['APP_ENV']   = 'testing';
        $_ENV['APP_DEBUG'] = 'true';
        $_ENV['DB_DRIVER'] = 'sqlite';

        /** @var array<string, mixed> $appConfig */
        $appConfig = require $this->basePath . '/config/app.php';
        /** @var array<string, mixed> $dbConfig */
        $dbConfig = require $this->basePath . '/config/database.php';
        $dbConfig['driver']   = 'sqlite';
        $dbConfig['database'] = ':memory:';

        date_default_timezone_set((string) ($appConfig['timezone'] ?? 'UTC'));

        $container = new Container();

        /** @var list<class-string> $providers */
        $providers = require $this->basePath . '/config/dependencies.php';
        foreach ($providers as $providerClass) {
            (new $providerClass())->register($container, $appConfig, $dbConfig);
        }

        /** @var Session $session */
        $session = $container->make(Session::class);
        $session->start();

        /** @var View $view */
        $view = $container->make(View::class);
        $view->share('session', $session);
        $view->share('csrf', $container->make(CsrfGuard::class));
        $view->share('auth', $container->make(Auth::class));
        $view->share('viewEngine', $view);

        $request = $container->make(Request::class);
        $router  = new Router(request: $request, container: $container);

        $register = require $this->basePath . '/config/routes.php';
        $register($router);

        $response = $router->handle();

        $this->assertInstanceOf(Response::class, $response);

        return $response;
    }
}
