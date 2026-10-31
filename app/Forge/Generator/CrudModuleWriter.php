<?php

declare(strict_types=1);

namespace App\Forge\Generator;

use App\Forge\Exception\GenerationException;

/**
 * Écrit un module CRUD PHP natif dans un projet starter déjà créé.
 */
final class CrudModuleWriter
{
    /**
     * @return list<string> chemins relatifs écrits
     */
    public function write(string $projectRoot, string $entity, bool $publicList = false): array
    {
        if (!preg_match('/^[A-Z][a-zA-Z0-9]*$/', $entity)) {
            throw new GenerationException('Nom d’entité invalide : ' . $entity);
        }
        if (!is_dir($projectRoot)) {
            throw new GenerationException('Projet introuvable : ' . $projectRoot);
        }

        $table = EntityNaming::toSnakePlural($entity);
        $viewDir = EntityNaming::toViewFolder($entity);
        $var = lcfirst($entity);
        $written = [];

        $written[] = $this->writeFile(
            $projectRoot . '/app/Models/' . $entity . '.php',
            $this->modelStub($entity)
        );
        $written[] = $this->writeFile(
            $projectRoot . '/app/Dao/' . $entity . 'Dao.php',
            $this->daoStub($entity, $table)
        );
        $written[] = $this->writeFile(
            $projectRoot . '/app/Controllers/' . $entity . 'Controller.php',
            $this->controllerStub($entity, $var, $table, $viewDir)
        );

        $migrationName = date('Y_m_d_His') . '_create_' . $table . '_table.php';
        $written[] = $this->writeFile(
            $projectRoot . '/database/migrations/' . $migrationName,
            $this->migrationStub($table)
        );

        foreach (['index', 'show', 'create', 'edit'] as $view) {
            $written[] = $this->writeFile(
                $projectRoot . '/views/' . $viewDir . '/' . $view . '.php',
                $this->viewStub($view, $entity, $table, $var)
            );
        }

        $this->ensureRoutesHook($projectRoot);
        $this->appendModuleRoutes($projectRoot, $entity, $table, $publicList);
        $this->ensureGeneratedProvider($projectRoot, $entity, $var);

        return $written;
    }

    private function writeFile(string $absolute, string $contents): string
    {
        $dir = dirname($absolute);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new GenerationException('Impossible de créer le dossier : ' . $dir);
        }
        if (is_file($absolute)) {
            throw new GenerationException('Fichier déjà présent (collision) : ' . $absolute);
        }
        if (file_put_contents($absolute, $contents) === false) {
            throw new GenerationException('Écriture impossible : ' . $absolute);
        }

        return str_replace('\\', '/', $absolute);
    }

    private function modelStub(string $entity): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Models;

class {$entity}
{
    public int \$id = 0;
    public string \$title = '';
    public string \$slug = '';
    public string \$body = '';
    public ?string \$published_at = null;
    public string \$created_at = '';
    public string \$updated_at = '';

    public function isPersisted(): bool
    {
        return \$this->id > 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => \$this->id,
            'title' => \$this->title,
            'slug' => \$this->slug,
            'body' => \$this->body,
            'published_at' => \$this->published_at,
            'created_at' => \$this->created_at,
            'updated_at' => \$this->updated_at,
        ];
    }
}

PHP;
    }

    private function daoStub(string $entity, string $table): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Dao;

use App\Models\\{$entity};
use Database\AbstractDao;

final class {$entity}Dao extends AbstractDao
{
    protected function getTable(): string
    {
        return '{$table}';
    }

    protected function getModelClass(): string
    {
        return {$entity}::class;
    }

    /** @return list<{$entity}> */
    public function findPublished(int \$limit = 20): array
    {
        \$stmt = \$this->pdo->prepare(
            'SELECT * FROM {$table} WHERE published_at IS NOT NULL ORDER BY published_at DESC LIMIT :lim'
        );
        \$stmt->bindValue(':lim', \$limit, \\PDO::PARAM_INT);
        \$stmt->execute();
        /** @var list<{$entity}>|false \$rows */
        \$rows = \$stmt->fetchAll(\\PDO::FETCH_CLASS, {$entity}::class);

        return is_array(\$rows) ? \$rows : [];
    }
}

PHP;
    }

    private function controllerStub(string $entity, string $var, string $table, string $viewDir): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Dao\\{$entity}Dao;
use Controller\AbstractController;
use Core\Exception\NotFoundException;
use Core\Http\RedirectResponse;
use Core\Http\Response;
use Core\Request;
use Core\Session;
use Core\View;

final class {$entity}Controller extends AbstractController
{
    public function __construct(
        View \$view,
        private readonly Request \$request,
        private readonly Session \$session,
        private readonly {$entity}Dao \${$var}Dao,
    ) {
        parent::__construct(\$view);
    }

    public function index(): Response
    {
        \$page = (int) \$this->request->query('page', 1);
        \$result = \$this->{$var}Dao->paginate(page: \$page, perPage: 15);

        return \$this->render('{$viewDir}/index', [
            'title' => '{$entity}',
            'items' => \$result['data'],
            'pages' => \$result['pages'],
            'current' => \$result['current'],
        ]);
    }

    public function show(string \$id): Response
    {
        \$item = \$this->{$var}Dao->findById((int) \$id)
            ?? throw new NotFoundException('{$entity} #' . \$id . ' introuvable.');

        return \$this->render('{$viewDir}/show', [
            'title' => \$item->title !== '' ? \$item->title : '{$entity} #' . \$id,
            'item' => \$item,
        ]);
    }

    public function create(): Response
    {
        return \$this->render('{$viewDir}/create', [
            'title' => 'Nouveau {$entity}',
            'errors' => [],
            'old' => [],
        ]);
    }

    public function store(): Response
    {
        \$data = \$this->request->post();
        \$title = trim((string) (\$data['title'] ?? ''));
        \$body = trim((string) (\$data['body'] ?? ''));
        \$errors = [];
        if (\$title === '') {
            \$errors['title'] = ['Le titre est requis.'];
        }
        if (\$errors !== []) {
            return \$this->render('{$viewDir}/create', [
                'title' => 'Nouveau {$entity}',
                'errors' => \$errors,
                'old' => \$data,
            ]);
        }

        \$slug = \$this->slugify(\$title);
        \$now = date('Y-m-d H:i:s');
        \$id = \$this->{$var}Dao->insert([
            'title' => \$title,
            'slug' => \$slug,
            'body' => \$body,
            'published_at' => !empty(\$data['publish']) ? \$now : null,
            'created_at' => \$now,
            'updated_at' => \$now,
        ]);
        \$this->session->flash('success', '{$entity} créé.');

        return new RedirectResponse('/{$table}/' . \$id);
    }

    public function edit(string \$id): Response
    {
        \$item = \$this->{$var}Dao->findById((int) \$id)
            ?? throw new NotFoundException('{$entity} #' . \$id . ' introuvable.');

        return \$this->render('{$viewDir}/edit', [
            'title' => 'Modifier {$entity}',
            'item' => \$item,
            'errors' => [],
        ]);
    }

    public function update(string \$id): Response
    {
        \$item = \$this->{$var}Dao->findById((int) \$id)
            ?? throw new NotFoundException('{$entity} #' . \$id . ' introuvable.');
        \$data = \$this->request->post();
        \$title = trim((string) (\$data['title'] ?? ''));
        \$body = trim((string) (\$data['body'] ?? ''));
        \$errors = [];
        if (\$title === '') {
            \$errors['title'] = ['Le titre est requis.'];
        }
        if (\$errors !== []) {
            return \$this->render('{$viewDir}/edit', [
                'title' => 'Modifier {$entity}',
                'item' => \$item,
                'errors' => \$errors,
            ]);
        }

        \$now = date('Y-m-d H:i:s');
        \$this->{$var}Dao->update((int) \$id, [
            'title' => \$title,
            'slug' => \$this->slugify(\$title),
            'body' => \$body,
            'published_at' => !empty(\$data['publish']) ? (\$item->published_at ?: \$now) : null,
            'updated_at' => \$now,
        ]);
        \$this->session->flash('success', '{$entity} mis à jour.');

        return new RedirectResponse('/{$table}/' . \$id);
    }

    public function destroy(string \$id): Response
    {
        \$this->{$var}Dao->delete((int) \$id);
        \$this->session->flash('success', '{$entity} supprimé.');

        return new RedirectResponse('/{$table}');
    }

    private function slugify(string \$title): string
    {
        \$slug = strtolower(trim(\$title));
        \$slug = preg_replace('/[^a-z0-9]+/', '-', \$slug) ?? \$slug;

        return trim(\$slug, '-') ?: 'item';
    }
}

PHP;
    }

    private function migrationStub(string $table): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

use Database\Migration\MigrationInterface;
use PDO;

return new class implements MigrationInterface
{
    public function up(PDO \$pdo): void
    {
        \$pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS {$table} (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                slug TEXT NOT NULL,
                body TEXT NOT NULL DEFAULT '',
                published_at TEXT NULL,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )
            SQL);
    }

    public function down(PDO \$pdo): void
    {
        \$pdo->exec('DROP TABLE IF EXISTS {$table}');
    }
};

PHP;
    }

    private function viewStub(string $view, string $entity, string $table, string $var): string
    {
        return match ($view) {
            'index' => <<<PHP
<?php /** @var list<object> \$items */ ?>
<section>
    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;margin-bottom:1rem">
        <h1 style="font-family:Fraunces,Georgia,serif;margin:0">{$entity}</h1>
        <a class="btn btn-primary" href="/{$table}/create">Nouveau</a>
    </div>
    <?php if (empty(\$items)): ?>
        <p style="color:var(--muted)">Aucun élément.</p>
    <?php else: ?>
        <ul style="list-style:none;padding:0;margin:0;display:grid;gap:0.75rem">
            <?php foreach (\$items as \$item): ?>
                <li style="border:1px solid var(--line);border-radius:12px;padding:1rem;background:var(--panel)">
                    <a href="/{$table}/<?= (int) \$item->id ?>" style="font-weight:600;color:inherit;text-decoration:none">
                        <?= htmlspecialchars((string) \$item->title, ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>
</section>
PHP,
            'show' => <<<PHP
<?php /** @var object \$item */ ?>
<section>
    <h1 style="font-family:Fraunces,Georgia,serif"><?= htmlspecialchars((string) \$item->title, ENT_QUOTES, 'UTF-8') ?></h1>
    <div style="white-space:pre-wrap;line-height:1.6;margin:1rem 0"><?= htmlspecialchars((string) \$item->body, ENT_QUOTES, 'UTF-8') ?></div>
    <div style="display:flex;gap:0.75rem">
        <a class="btn btn-ghost" href="/{$table}/<?= (int) \$item->id ?>/edit">Modifier</a>
        <a class="btn btn-ghost" href="/{$table}">Retour</a>
    </div>
</section>
PHP,
            'create', 'edit' => <<<PHP
<?php
/** @var array<string, list<string>> \$errors */
\$isEdit = isset(\$item);
\$action = \$isEdit ? '/{$table}/' . (int) \$item->id : '/{$table}';
\$titleVal = \$isEdit ? (string) \$item->title : (string) (\$old['title'] ?? '');
\$bodyVal = \$isEdit ? (string) \$item->body : (string) (\$old['body'] ?? '');
\$published = \$isEdit ? \$item->published_at !== null : !empty(\$old['publish']);
?>
<section class="auth-wrap">
    <form method="POST" action="<?= htmlspecialchars(\$action, ENT_QUOTES, 'UTF-8') ?>" class="auth-card">
        <?= \$csrf->field() ?>
        <h1 style="font-family:Fraunces,Georgia,serif;text-align:center"><?= \$isEdit ? 'Modifier' : 'Nouveau' ?> {$entity}</h1>
        <div class="field">
            <label for="title">Titre</label>
            <input class="input" id="title" name="title" value="<?= htmlspecialchars(\$titleVal, ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (!empty(\$errors['title'])): ?><p class="field-error"><?= htmlspecialchars(\$errors['title'][0], ENT_QUOTES, 'UTF-8') ?></p><?php endif ?>
        </div>
        <div class="field">
            <label for="body">Contenu</label>
            <textarea class="input" id="body" name="body" rows="8"><?= htmlspecialchars(\$bodyVal, ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <label style="display:flex;gap:0.5rem;align-items:center;margin:0.75rem 0 1rem">
            <input type="checkbox" name="publish" value="1" <?= \$published ? 'checked' : '' ?>>
            Publier
        </label>
        <button type="submit" class="btn btn-primary" style="cursor:pointer;font:inherit;width:100%">Enregistrer</button>
    </form>
</section>
PHP,
            default => throw new GenerationException('Vue inconnue : ' . $view),
        };
    }

    private function ensureRoutesHook(string $projectRoot): void
    {
        $routes = $projectRoot . '/config/routes.php';
        if (!is_file($routes)) {
            throw new GenerationException('config/routes.php introuvable dans le projet généré.');
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
            throw new GenerationException('Impossible d’injecter le hook forge_routes dans routes.php.');
        }
        file_put_contents($routes, $patched);
    }

    private function appendModuleRoutes(string $projectRoot, string $entity, string $table, bool $publicList): void
    {
        $path = $projectRoot . '/config/forge_routes.php';
        if (!is_file($path)) {
            $header = <<<'PHP'
<?php

declare(strict_types=1);

use Core\Auth\Middleware\AuthMiddleware;
use Core\Router;

return function (Router $router): void {
PHP;
            file_put_contents($path, $header . "\n};\n");
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new GenerationException('Lecture forge_routes.php impossible.');
        }
        if (str_contains($contents, $entity . 'Controller::class')) {
            return;
        }

        $authIndex = $publicList ? '' : '->middleware(AuthMiddleware::class)';
        $authShow = $publicList ? '' : '->middleware(AuthMiddleware::class)';
        $block = "\n    // --- Forge module {$entity} ---\n"
            . "    \$router->get('/{$table}', \\App\\Controllers\\{$entity}Controller::class, 'index'){$authIndex};\n"
            . "    \$router->get('/{$table}/create', \\App\\Controllers\\{$entity}Controller::class, 'create')->middleware(AuthMiddleware::class);\n"
            . "    \$router->post('/{$table}', \\App\\Controllers\\{$entity}Controller::class, 'store')->middleware(AuthMiddleware::class);\n"
            . "    \$router->get('/{$table}/:id', \\App\\Controllers\\{$entity}Controller::class, 'show'){$authShow};\n"
            . "    \$router->get('/{$table}/:id/edit', \\App\\Controllers\\{$entity}Controller::class, 'edit')->middleware(AuthMiddleware::class);\n"
            . "    \$router->post('/{$table}/:id', \\App\\Controllers\\{$entity}Controller::class, 'update')->middleware(AuthMiddleware::class);\n"
            . "    \$router->post('/{$table}/:id/delete', \\App\\Controllers\\{$entity}Controller::class, 'destroy')->middleware(AuthMiddleware::class);\n";

        $patched = preg_replace('/\};\s*$/', $block . "};\n", $contents, 1);
        if ($patched === null) {
            throw new GenerationException('Patch forge_routes.php échoué.');
        }
        file_put_contents($path, $patched);
    }

    private function ensureGeneratedProvider(string $projectRoot, string $entity, string $var): void
    {
        $path = $projectRoot . '/app/Providers/ForgeGeneratedServiceProvider.php';
        if (!is_file($path)) {
            $stub = <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Providers;

use Core\Container;
use Core\Request;
use Core\ServiceProviderInterface;
use Core\Session;
use Core\View;

final class ForgeGeneratedServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container, array $appConfig, array $dbConfig): void
    {
        // Bindings injectés par Astral Forge — ne pas supprimer le marqueur.
        // @forge-bindings
    }
}

PHP;
            file_put_contents($path, $stub);
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new GenerationException('Lecture ForgeGeneratedServiceProvider impossible.');
        }
        if (!str_contains($contents, $entity . 'Dao::class')) {
            $binding = <<<PHP
        \$container->singleton(\\App\\Dao\\{$entity}Dao::class, fn (Container \$c) => new \\App\\Dao\\{$entity}Dao(\$c->make(\\PDO::class)));
        \$container->bind(\\App\\Controllers\\{$entity}Controller::class, fn (Container \$c) => new \\App\\Controllers\\{$entity}Controller(
            \$c->make(View::class),
            \$c->make(Request::class),
            \$c->make(Session::class),
            \$c->make(\\App\\Dao\\{$entity}Dao::class),
        ));

PHP;
            $contents = str_replace(
                '// @forge-bindings',
                "// @forge-bindings\n" . $binding,
                $contents
            );
            file_put_contents($path, $contents);
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
            if (!str_contains($depContents, 'ForgeGeneratedServiceProvider')) {
                throw new GenerationException('Impossible d’enregistrer ForgeGeneratedServiceProvider.');
            }
            file_put_contents($deps, $depContents);
        }
    }
}
