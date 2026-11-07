<?php

declare(strict_types=1);

namespace App\Forge\Atelier;

use App\Forge\Generator\EntityNaming;
use App\Models\ForgeProject;

/**
 * Lecture légère d’un projet généré (modules présents).
 */
final class ProjectInspector
{
    /**
     * @return list<array{entity: string, table: string, route: string}>
     */
    public function modules(ForgeProject $project): array
    {
        if (!$project->directoryExists()) {
            return [];
        }

        $dir = $project->path . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Models';
        if (!is_dir($dir)) {
            return [];
        }

        $modules = [];
        foreach (scandir($dir) ?: [] as $file) {
            if (!str_ends_with($file, '.php')) {
                continue;
            }
            $entity = substr($file, 0, -4);
            if ($entity === '' || $entity === 'User' || !preg_match('/^[A-Z][a-zA-Z0-9]*$/', $entity)) {
                continue;
            }
            $table = EntityNaming::toSnakePlural($entity);
            $modules[] = [
                'entity' => $entity,
                'table' => $table,
                'route' => '/' . $table,
            ];
        }

        usort(
            $modules,
            static fn (array $a, array $b): int => strcmp($a['entity'], $b['entity']),
        );

        return $modules;
    }
}
