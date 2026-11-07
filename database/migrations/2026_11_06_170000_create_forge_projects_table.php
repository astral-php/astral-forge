<?php

declare(strict_types=1);

use Database\Migration\Migration;

/**
 * Registre atelier — projets générés par Forge.
 */
class CreateForgeProjectsTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $pdo->exec(<<<'SQL'
                CREATE TABLE IF NOT EXISTS forge_projects (
                    id         INTEGER PRIMARY KEY AUTOINCREMENT,
                    name       TEXT    NOT NULL,
                    path       TEXT    NOT NULL UNIQUE,
                    recipe     TEXT    NOT NULL DEFAULT 'blank',
                    created_at TEXT    NOT NULL DEFAULT (datetime('now')),
                    updated_at TEXT    NOT NULL DEFAULT (datetime('now'))
                )
            SQL);

            return;
        }

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS `forge_projects` (
                `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name`       VARCHAR(180) NOT NULL,
                `path`       VARCHAR(500) NOT NULL,
                `recipe`     VARCHAR(64)  NOT NULL DEFAULT 'blank',
                `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `forge_projects_path_unique` (`path`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS forge_projects');
    }
}
