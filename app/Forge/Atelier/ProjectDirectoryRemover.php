<?php

declare(strict_types=1);

namespace App\Forge\Atelier;

use App\Forge\Exception\UnsafePathException;
use App\Forge\Security\SecurePathGuard;
use RuntimeException;
use Throwable;

/**
 * Efface récursivement un dossier projet déjà enregistré (chemin confiné).
 */
final class ProjectDirectoryRemover
{
    public function __construct(
        private readonly SecurePathGuard $pathGuard,
    ) {
    }

    /**
     * @throws UnsafePathException
     * @throws RuntimeException
     */
    public function delete(string $projectPath): void
    {
        $resolved = $this->pathGuard->resolveDeletableProjectDirectory($projectPath);
        $this->rmTree($resolved);

        if (is_dir($resolved) || is_file($resolved)) {
            throw new RuntimeException('Suppression incomplète : ' . $resolved);
        }
    }

    private function rmTree(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            if (!@unlink($path)) {
                throw new RuntimeException('Impossible de supprimer le fichier : ' . $path);
            }

            return;
        }

        if (!is_dir($path)) {
            return;
        }

        $items = @scandir($path);
        if ($items === false) {
            throw new RuntimeException('Lecture impossible : ' . $path);
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $child = $path . DIRECTORY_SEPARATOR . $item;
            try {
                $this->rmTree($child);
            } catch (Throwable $e) {
                throw new RuntimeException($e->getMessage(), 0, $e);
            }
        }

        if (!@rmdir($path)) {
            throw new RuntimeException('Impossible de supprimer le dossier : ' . $path);
        }
    }
}
