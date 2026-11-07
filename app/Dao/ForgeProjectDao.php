<?php

declare(strict_types=1);

namespace App\Dao;

use App\Models\ForgeProject;
use PDO;

/**
 * Registre local des projets générés (atelier 0.5).
 */
final class ForgeProjectDao
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function createTableIfNotExists(): void
    {
        $this->pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS forge_projects (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                name       TEXT    NOT NULL,
                path       TEXT    NOT NULL UNIQUE,
                recipe     TEXT    NOT NULL DEFAULT 'blank',
                created_at TEXT    NOT NULL DEFAULT (datetime('now')),
                updated_at TEXT    NOT NULL DEFAULT (datetime('now'))
            );
        SQL);
    }

    /**
     * @return list<ForgeProject>
     */
    public function allOrdered(): array
    {
        $stmt = $this->pdo->query(
            'SELECT * FROM forge_projects ORDER BY updated_at DESC, id DESC'
        );
        if ($stmt === false) {
            return [];
        }

        /** @var list<ForgeProject> */
        return $stmt->fetchAll(PDO::FETCH_CLASS, ForgeProject::class) ?: [];
    }

    public function find(int $id): ?ForgeProject
    {
        $stmt = $this->pdo->prepare('SELECT * FROM forge_projects WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, ForgeProject::class);
        $row = $stmt->fetch();

        return $row instanceof ForgeProject ? $row : null;
    }

    public function findByPath(string $path): ?ForgeProject
    {
        $stmt = $this->pdo->prepare('SELECT * FROM forge_projects WHERE path = :path LIMIT 1');
        $stmt->execute([':path' => $this->normalizePath($path)]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, ForgeProject::class);
        $row = $stmt->fetch();

        return $row instanceof ForgeProject ? $row : null;
    }

    public function upsert(string $name, string $path, string $recipe): ForgeProject
    {
        $path = $this->normalizePath($path);
        $name = trim($name) !== '' ? trim($name) : basename($path);
        $recipe = trim($recipe) !== '' ? trim($recipe) : 'blank';
        $now = date('Y-m-d H:i:s');

        $existing = $this->findByPath($path);
        if ($existing !== null) {
            $stmt = $this->pdo->prepare(
                'UPDATE forge_projects
                 SET name = :name, recipe = :recipe, updated_at = :updated_at
                 WHERE id = :id'
            );
            $stmt->execute([
                ':name' => $name,
                ':recipe' => $recipe,
                ':updated_at' => $now,
                ':id' => $existing->id,
            ]);

            $fresh = $this->find($existing->id);
            if ($fresh === null) {
                throw new \RuntimeException('Mise à jour forge_projects impossible.');
            }

            return $fresh;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO forge_projects (name, path, recipe, created_at, updated_at)
             VALUES (:name, :path, :recipe, :created_at, :updated_at)'
        );
        $stmt->execute([
            ':name' => $name,
            ':path' => $path,
            ':recipe' => $recipe,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $id = (int) $this->pdo->lastInsertId();
        $fresh = $this->find($id);
        if ($fresh === null) {
            throw new \RuntimeException('Insertion forge_projects impossible.');
        }

        return $fresh;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM forge_projects WHERE id = :id');

        return $stmt->execute([':id' => $id]) && $stmt->rowCount() > 0;
    }

    public function touch(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE forge_projects SET updated_at = :updated_at WHERE id = :id'
        );
        $stmt->execute([
            ':updated_at' => date('Y-m-d H:i:s'),
            ':id' => $id,
        ]);
    }

    private function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = preg_replace('#(?<!:)/+#', '/', $path) ?? $path;

        return rtrim($path, '/') ?: $path;
    }
}
