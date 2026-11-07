<?php

declare(strict_types=1);

namespace App\Forge\Security;

use App\Forge\Exception\UnsafePathException;

/**
 * Confine les destinations à des racines autorisées.
 * Les `..` sont résolus puis le résultat est vérifié (pas de sortie de sandbox).
 */
final class SecurePathGuard
{
    /** @param list<string> $allowedRoots */
    public function __construct(
        private readonly array $allowedRoots,
        private readonly string $basePath,
    ) {
    }

    /**
     * Résout une destination relative ou absolue en chemin absolu normalisé.
     * Le parent doit exister ; la cible peut ne pas exister encore.
     */
    public function resolveDestination(string $destination): string
    {
        $destination = trim($destination);
        if ($destination === '') {
            throw new UnsafePathException('Destination vide.');
        }

        $absolute = $this->toAbsolute($destination);
        $normalized = $this->resolveDotSegments($absolute);

        $parent = dirname($normalized);
        $realParent = realpath($parent);
        if ($realParent === false || !is_dir($realParent)) {
            throw new UnsafePathException('Le répertoire parent de la destination n’existe pas.');
        }

        $resolved = $this->normalize($realParent . DIRECTORY_SEPARATOR . basename($normalized));
        $this->assertWithinAllowed($resolved);

        return $resolved;
    }

    /**
     * Résout un dossier projet déjà présent et confinable.
     */
    public function resolveExistingDirectory(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            throw new UnsafePathException('Chemin projet vide.');
        }

        $absolute = $this->toAbsolute($path);
        $normalized = $this->resolveDotSegments($absolute);
        $real = realpath($normalized);
        if ($real === false || !is_dir($real)) {
            throw new UnsafePathException('Projet introuvable ou inaccessible.');
        }

        $resolved = $this->normalize($real);
        $this->assertWithinAllowed($resolved);

        return $resolved;
    }

    /**
     * Comme resolveExistingDirectory, mais refuse de cibler une racine autorisée
     * (évite d’effacer tout le sandbox FORGE_ALLOWED_ROOT).
     */
    public function resolveDeletableProjectDirectory(string $path): string
    {
        $resolved = $this->resolveExistingDirectory($path);
        $normalizedResolved = $this->normalize($resolved);

        foreach ($this->allowedRoots as $root) {
            $absoluteRoot = $this->isAbsolute($root)
                ? $root
                : $this->basePath . DIRECTORY_SEPARATOR . $root;
            $realRoot = realpath($absoluteRoot);
            if ($realRoot === false) {
                continue;
            }
            if ($normalizedResolved === $this->normalize($realRoot)) {
                throw new UnsafePathException(
                    'Impossible de supprimer une racine autorisée (FORGE_ALLOWED_ROOT).'
                );
            }
        }

        return $resolved;
    }

    private function toAbsolute(string $destination): string
    {
        if ($this->isAbsolute($destination)) {
            return $this->normalize($destination);
        }

        return $this->normalize($this->basePath . DIRECTORY_SEPARATOR . $destination);
    }

    private function assertWithinAllowed(string $resolved): void
    {
        $normalizedResolved = $this->normalize($resolved);
        $resolvedParent = $this->normalize(dirname($normalizedResolved));

        foreach ($this->allowedRoots as $root) {
            $absoluteRoot = $this->isAbsolute($root)
                ? $root
                : $this->basePath . DIRECTORY_SEPARATOR . $root;
            $realRoot = realpath($absoluteRoot);
            if ($realRoot === false) {
                continue;
            }
            $normalizedRoot = $this->normalize($realRoot);
            if (
                $normalizedResolved === $normalizedRoot
                || str_starts_with($normalizedResolved, $normalizedRoot . '/')
                || $resolvedParent === $normalizedRoot
                || str_starts_with($resolvedParent, $normalizedRoot . '/')
            ) {
                return;
            }
        }

        throw new UnsafePathException(
            'Destination hors racines autorisées (FORGE_ALLOWED_ROOT / temp).'
        );
    }

    private function resolveDotSegments(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $drive = '';
        if (preg_match('#^([A-Za-z]:)(/.*)$#', $path, $m) === 1) {
            $drive = $m[1];
            $path = $m[2];
        }

        $parts = explode('/', $path);
        $stack = [];
        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                if ($stack === []) {
                    throw new UnsafePathException('Traversal hors de la racine du chemin.');
                }
                array_pop($stack);
                continue;
            }
            $stack[] = $part;
        }

        if ($drive !== '') {
            return $drive . '/' . implode('/', $stack);
        }

        return '/' . implode('/', $stack);
    }

    private function isAbsolute(string $path): bool
    {
        if (str_starts_with($path, '/') || str_starts_with($path, '\\')) {
            return true;
        }

        return (bool) preg_match('#^[A-Za-z]:[\\\\/]#', $path);
    }

    private function normalize(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#(?<!:)/+#', '/', $path) ?? $path;

        return rtrim($path, '/') ?: $path;
    }
}
