<?php

declare(strict_types=1);

namespace App\Providers;

use App\Controllers\Admin\UserController as AdminUserController;
use App\Controllers\AuthController;
use App\Controllers\ForgeApiController;
use App\Controllers\ForgeController;
use App\Controllers\ForgeProjectsController;
use App\Controllers\HomeController;
use App\Controllers\SetupController;
use App\Controllers\SiteController;
use App\Controllers\ProfileController;
use App\Controllers\UserController;
use App\Dao\ForgeProjectDao;
use App\Dao\UserDao;
use App\Forge\Install\InstallGate;
use App\Forge\Atelier\AtelierService;
use App\Forge\Atelier\ProjectConsoleRunner;
use App\Forge\Atelier\ProjectDirectoryRemover;
use App\Forge\Atelier\ProjectRegistry;
use App\Forge\Generator\CrudModuleWriter;
use App\Forge\Job\CliJobStore;
use App\Forge\Job\DetachedCliLauncher;
use App\Events\RoleChanged;
use App\Events\UserLoggedIn;
use App\Events\UserRegistered;
use App\Forge\Blueprint\BlueprintValidator;
use App\Forge\Plan\GenerationPlanner;
use App\Forge\ProjectGenerationService;
use App\Forge\Resolver\DependencyResolver;
use App\Forge\Security\SecurePathGuard;
use App\Forge\Job\DetachedJobLauncher;
use App\Forge\Job\GenerationJobStore;
use App\Forge\Runtime\RuntimeHealth;
use App\Forge\Runtime\ToolchainResolver;
use App\Forge\Writer\ComposerCreateProjectRunner;
use App\Forge\Writer\PlanExecutor;
use App\Listeners\LogRoleChange;
use App\Listeners\LogUserActivity;
use App\Listeners\SendWelcomeEmail;
use Core\Auth\Auth;
use Core\Container;
use Core\Events\EventDispatcher;
use Core\Mailer\Mailer;
use Core\Request;
use Core\ServiceProviderInterface;
use Core\Session;
use Core\View;

/**
 * Services Forge + auth locale (shell starter).
 */
final class AppServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container, array $appConfig, array $dbConfig): void
    {
        /** @var EventDispatcher $dispatcher */
        $dispatcher = $container->make(EventDispatcher::class);

        $dispatcher->listen(UserRegistered::class, SendWelcomeEmail::class);
        $dispatcher->listen(UserRegistered::class, LogUserActivity::class);
        $dispatcher->listen(UserLoggedIn::class,   LogUserActivity::class);
        $dispatcher->listen(RoleChanged::class,    LogRoleChange::class);

        $container->singleton(UserDao::class, function (Container $c) use ($dbConfig): UserDao {
            $dao = new UserDao($c->make(\PDO::class));

            if (($dbConfig['driver'] ?? 'sqlite') === 'sqlite') {
                $dao->createTableIfNotExists();
            }

            return $dao;
        });

        $container->singleton(ForgeProjectDao::class, function (Container $c) use ($dbConfig): ForgeProjectDao {
            $dao = new ForgeProjectDao($c->make(\PDO::class));

            if (($dbConfig['driver'] ?? 'sqlite') === 'sqlite') {
                $dao->createTableIfNotExists();
            }

            return $dao;
        });

        $container->singleton(InstallGate::class, function (Container $c): InstallGate {
            return InstallGate::forBasePath($c->make(\PDO::class), BASE_PATH);
        });

        $container->singleton(RuntimeHealth::class, function (): RuntimeHealth {
            /** @var array{allowed_roots: list<string>} $forgeConfig */
            $forgeConfig = require BASE_PATH . '/config/forge.php';

            return new RuntimeHealth(BASE_PATH, $forgeConfig['allowed_roots']);
        });

        $container->singleton(Mailer::class, function () use ($appConfig): Mailer {
            /** @var array<string, mixed> $mail */
            $mail = $appConfig['mail'] ?? [];

            return new Mailer(
                driver:     (string) ($mail['driver']     ?? 'mail'),
                host:       (string) ($mail['host']       ?? 'localhost'),
                port:       (int)    ($mail['port']       ?? 25),
                username:   (string) ($mail['username']   ?? ''),
                password:   (string) ($mail['password']   ?? ''),
                encryption: (string) ($mail['encryption'] ?? ''),
                from:       (string) ($mail['from']       ?? 'noreply@localhost'),
                fromName:   (string) ($mail['from_name']  ?? 'Astral Forge'),
            );
        });

        $container->singleton(ProjectGenerationService::class, function (Container $c): ProjectGenerationService {
            /** @var array{
             *     allowed_roots: list<string>,
             *     starter_package: string,
             *     php_bin: string,
             *     composer_bin: string,
             *     supported_recipes: list<string>,
             *     supported_targets: list<string>
             * } $forgeConfig
             */
            $forgeConfig = require BASE_PATH . '/config/forge.php';

            $validator = new BlueprintValidator(
                $forgeConfig['supported_recipes'],
                $forgeConfig['supported_targets'],
            );
            $planner = new GenerationPlanner($forgeConfig['starter_package']);
            $pathGuard = new SecurePathGuard($forgeConfig['allowed_roots'], BASE_PATH);
            $toolchain = new ToolchainResolver(
                (string) ($forgeConfig['php_bin'] ?? ''),
                (string) ($forgeConfig['composer_bin'] ?? ''),
            );
            $health = $c->make(RuntimeHealth::class);
            $composerRunner = new ComposerCreateProjectRunner(
                $toolchain,
                allowApacheComposer: (bool) ($forgeConfig['allow_apache_composer'] ?? false),
                health: $health,
            );
            $executor = new PlanExecutor($composerRunner);
            $registry = new ProjectRegistry($c->make(ForgeProjectDao::class), $pathGuard);

            return new ProjectGenerationService(
                $validator,
                new DependencyResolver(),
                $planner,
                $pathGuard,
                $executor,
                $composerRunner,
                new GenerationJobStore(),
                new DetachedJobLauncher($toolchain, $health),
                $registry,
            );
        });

        $container->singleton(ProjectRegistry::class, function (Container $c): ProjectRegistry {
            /** @var array{allowed_roots: list<string>} $forgeConfig */
            $forgeConfig = require BASE_PATH . '/config/forge.php';

            return new ProjectRegistry(
                $c->make(ForgeProjectDao::class),
                new SecurePathGuard($forgeConfig['allowed_roots'], BASE_PATH),
            );
        });

        $container->singleton(AtelierService::class, function (Container $c): AtelierService {
            /** @var array{php_bin: string, composer_bin: string, allowed_roots: list<string>} $forgeConfig */
            $forgeConfig = require BASE_PATH . '/config/forge.php';
            $toolchain = new ToolchainResolver(
                (string) ($forgeConfig['php_bin'] ?? ''),
                (string) ($forgeConfig['composer_bin'] ?? ''),
            );
            $composerRunner = new ComposerCreateProjectRunner(
                $toolchain,
                health: $c->make(RuntimeHealth::class),
            );
            $console = new ProjectConsoleRunner(
                $toolchain,
                new CliJobStore(),
                new DetachedCliLauncher($toolchain),
                $composerRunner,
            );
            $pathGuard = new SecurePathGuard($forgeConfig['allowed_roots'], BASE_PATH);

            return new AtelierService(
                $c->make(ProjectRegistry::class),
                $console,
                new ProjectDirectoryRemover($pathGuard),
                new CrudModuleWriter(),
            );
        });

        $container->bind(HomeController::class, fn(Container $c) => new HomeController(
            view:        $c->make(View::class),
            userDao:     $c->make(UserDao::class),
            version:     (string) ($appConfig['version'] ?? '1.0.0'),
            githubUrl:   (string) ($appConfig['github_url'] ?? 'https://github.com/astral-php/astral-forge'),
            websiteUrl:  (string) ($appConfig['website_url'] ?? 'https://astral-php.dev'),
        ));

        $container->bind(SiteController::class, fn(Container $c) => new SiteController(
            view:       $c->make(View::class),
            version:    (string) ($appConfig['version'] ?? '1.0.0'),
            githubUrl:  (string) ($appConfig['github_url'] ?? 'https://github.com/astral-php/astral-forge'),
            websiteUrl: (string) ($appConfig['website_url'] ?? 'https://astral-php.dev'),
        ));

        $container->bind(SetupController::class, fn(Container $c) => new SetupController(
            view:    $c->make(View::class),
            session: $c->make(Session::class),
            install: $c->make(InstallGate::class),
        ));

        $container->bind(ForgeController::class, fn(Container $c) => new ForgeController(
            view:    $c->make(View::class),
            request: $c->make(Request::class),
            session: $c->make(Session::class),
            forge:   $c->make(ProjectGenerationService::class),
            install: $c->make(InstallGate::class),
        ));

        $container->bind(ForgeApiController::class, fn(Container $c) => new ForgeApiController(
            view:    $c->make(View::class),
            request: $c->make(Request::class),
            forge:   $c->make(ProjectGenerationService::class),
            atelier: $c->make(AtelierService::class),
            install: $c->make(InstallGate::class),
        ));

        $container->bind(ForgeProjectsController::class, fn(Container $c) => new ForgeProjectsController(
            view:     $c->make(View::class),
            request:  $c->make(Request::class),
            session:  $c->make(Session::class),
            registry: $c->make(ProjectRegistry::class),
            atelier:  $c->make(AtelierService::class),
            install:  $c->make(InstallGate::class),
        ));

        $container->bind(UserController::class, fn(Container $c) => new UserController(
            view:    $c->make(View::class),
            request: $c->make(Request::class),
            userDao: $c->make(UserDao::class),
            session: $c->make(Session::class),
            auth:    $c->make(Auth::class),
        ));

        $container->bind(ProfileController::class, fn(Container $c) => new ProfileController(
            view:    $c->make(View::class),
            request: $c->make(Request::class),
            auth:    $c->make(Auth::class),
            userDao: $c->make(UserDao::class),
            session: $c->make(Session::class),
        ));

        $container->bind(AdminUserController::class, fn(Container $c) => new AdminUserController(
            view:       $c->make(View::class),
            request:    $c->make(Request::class),
            userDao:    $c->make(UserDao::class),
            session:    $c->make(Session::class),
            auth:       $c->make(Auth::class),
            dispatcher: $c->make(EventDispatcher::class),
        ));

        $container->bind(AuthController::class, fn(Container $c) => new AuthController(
            view:             $c->make(View::class),
            request:          $c->make(Request::class),
            auth:             $c->make(Auth::class),
            userDao:          $c->make(UserDao::class),
            session:          $c->make(Session::class),
            mailer:           $c->make(Mailer::class),
            dispatcher:       $c->make(EventDispatcher::class),
            registrationMode: (string) ($appConfig['auth_registration'] ?? 'direct'),
            appBaseUrl:       (string) ($appConfig['base_url'] ?? ''),
        ));
    }
}
