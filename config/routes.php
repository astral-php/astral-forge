<?php

declare(strict_types=1);

use App\Controllers\Admin\UserController as AdminUserController;
use App\Controllers\AuthController;
use App\Controllers\ForgeApiController;
use App\Controllers\ForgeController;
use App\Controllers\ForgeProjectsController;
use App\Controllers\HomeController;
use App\Controllers\ProfileController;
use App\Controllers\SiteController;
use App\Controllers\UserController;
use Core\Auth\Middleware\AdminMiddleware;
use Core\Auth\Middleware\AuthMiddleware;
use Core\Auth\Middleware\GuestMiddleware;
use Core\Router;

return function (Router $router): void {

    $router->get('/', HomeController::class, 'index');

    $router->get('/templates', SiteController::class, 'templates');
    $router->get('/docs', SiteController::class, 'docs');
    $router->get('/discover', SiteController::class, 'discover');
    $router->get('/extensions', SiteController::class, 'extensions');
    $router->get('/news', SiteController::class, 'news');

    $router->get('/forge', ForgeProjectsController::class, 'index');
    $router->get('/forge/new', ForgeController::class, 'create');
    $router->get('/forge/projects/:id', ForgeProjectsController::class, 'show');
    $router->post('/forge/projects/:id/delete', ForgeProjectsController::class, 'destroy');
    $router->post('/forge/projects/:id/purge', ForgeProjectsController::class, 'purge');
    $router->post('/forge/projects/:id/migrate', ForgeProjectsController::class, 'migrate');
    $router->post('/forge/projects/:id/migrate-rollback', ForgeProjectsController::class, 'migrateRollback');
    $router->post('/forge/projects/:id/modules', ForgeProjectsController::class, 'addModule');
    $router->post('/forge/plan', ForgeController::class, 'plan');
    $router->post('/forge/generate', ForgeController::class, 'generate');
    $router->get('/forge/api/environment', ForgeApiController::class, 'environment');
    $router->get('/forge/api/jobs/:id', ForgeApiController::class, 'job');
    $router->get('/forge/api/cli-jobs/:id', ForgeApiController::class, 'cliJob');
    $router->post('/forge/api/plan', ForgeApiController::class, 'plan');
    $router->post('/forge/api/generate', ForgeApiController::class, 'generate');

    $router->get('/login',     AuthController::class, 'loginForm')->middleware(GuestMiddleware::class);
    $router->post('/login',    AuthController::class, 'login')->middleware(GuestMiddleware::class);
    $router->post('/logout',   AuthController::class, 'logout');

    $router->get('/register',  AuthController::class, 'registerForm')->middleware(GuestMiddleware::class);
    $router->post('/register', AuthController::class, 'register')->middleware(GuestMiddleware::class);

    $router->get('/verify-pending', AuthController::class, 'verifyPending');
    $router->get('/verify-email',   AuthController::class, 'verifyEmail');

    $router->get('/forgot-password',  AuthController::class, 'forgotPasswordForm')->middleware(GuestMiddleware::class);
    $router->post('/forgot-password', AuthController::class, 'forgotPassword')->middleware(GuestMiddleware::class);
    $router->get('/reset-password',   AuthController::class, 'resetPasswordForm')->middleware(GuestMiddleware::class);
    $router->post('/reset-password',  AuthController::class, 'resetPassword')->middleware(GuestMiddleware::class);

    $router->group('', function (Router $r): void {
        $r->get('/profile',           ProfileController::class, 'editForm');
        $r->post('/profile',          ProfileController::class, 'update');
        $r->post('/profile/password', ProfileController::class, 'updatePassword');
    }, [AuthMiddleware::class]);

    $router->group('', function (Router $r): void {
        $r->get('/users',     UserController::class, 'index');
        $r->get('/users/:id', UserController::class, 'show');
    }, [AuthMiddleware::class]);

    $router->group('', function (Router $r): void {
        $r->get('/users/create',      UserController::class, 'create');
        $r->post('/users',            UserController::class, 'store');
        $r->post('/users/:id/delete', UserController::class, 'destroy');
    }, [AdminMiddleware::class]);

    $router->group('/admin', function (Router $r): void {
        $r->get('/users',           AdminUserController::class, 'index');
        $r->post('/users/:id/role', AdminUserController::class, 'updateRole');
    }, [AdminMiddleware::class]);
};
