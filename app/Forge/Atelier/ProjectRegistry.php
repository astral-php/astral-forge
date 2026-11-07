<?php

declare(strict_types=1);

namespace App\Forge\Atelier;

use App\Dao\ForgeProjectDao;
use App\Forge\Security\SecurePathGuard;
use App\Models\ForgeProject;

/**
 * Enregistre / retrouve les projets générés (chemins confinés).
 */
final class ProjectRegistry
{
    public function __construct(
        private readonly ForgeProjectDao $dao,
        private readonly SecurePathGuard $pathGuard,
    ) {
    }

    /**
     * @param array<string, mixed> $blueprintData
     */
    public function recordFromBlueprint(array $blueprintData, string $absoluteDestination): ForgeProject
    {
        $path = $this->pathGuard->resolveExistingDirectory($absoluteDestination);
        $project = $blueprintData['project'] ?? [];
        $name = is_array($project)
            ? trim((string) ($project['name'] ?? ''))
            : '';
        if ($name === '') {
            $name = basename(str_replace('\\', '/', $path));
        }
        $recipe = trim((string) ($blueprintData['recipe'] ?? 'blank'));
        if ($recipe === '') {
            $recipe = 'blank';
        }

        return $this->dao->upsert($name, $path, $recipe);
    }

    /**
     * @return list<ForgeProject>
     */
    public function all(): array
    {
        return $this->dao->allOrdered();
    }

    public function find(int $id): ?ForgeProject
    {
        return $this->dao->find($id);
    }

    /**
     * @throws \App\Forge\Exception\UnsafePathException
     */
    public function requireExisting(int $id): ForgeProject
    {
        $project = $this->dao->find($id);
        if ($project === null) {
            throw new \InvalidArgumentException('Projet introuvable dans le registre.');
        }
        if (!$project->directoryExists()) {
            throw new \RuntimeException(
                'Le dossier du projet a disparu. Vous pouvez le retirer du registre.'
            );
        }
        $this->pathGuard->resolveExistingDirectory($project->path);

        return $project;
    }

    public function remove(int $id): bool
    {
        return $this->dao->delete($id);
    }

    public function touch(int $id): void
    {
        $this->dao->touch($id);
    }
}
