<?php

declare(strict_types=1);

namespace App\Forge\Runtime;

/**
 * Droits d’écriture + COMPOSER_HOME pour generate web/CLI (Win + Linux FPM).
 */
final class RuntimeHealth
{
    public function __construct(
        private readonly string $basePath,
        /** @var list<string> */
        private readonly array $allowedRoots = [],
    ) {
    }

    /**
     * @return array{
     *     path: string,
     *     writable: bool,
     *     source: 'env'|'home'|'storage'|'temp',
     *     hint: ?string
     * }
     */
    public function composerHomeStatus(): array
    {
        [$path, $source] = $this->resolveComposerHome();
        $writable = $this->ensureWritableDir($path);
        $hint = $writable
            ? null
            : 'COMPOSER_HOME non writable (« ' . $path . ' »). '
                . 'Définissez COMPOSER_HOME vers un dossier accessible à l’utilisateur PHP '
                . '(ex. ' . $this->storageComposerHome() . '), ou chmod/chown adaptés.';

        return [
            'path' => $path,
            'writable' => $writable,
            'source' => $source,
            'hint' => $hint,
        ];
    }

    public function resolveComposerHomePath(): string
    {
        return $this->resolveComposerHome()[0];
    }

    /**
     * @return list<array{path: string, writable: bool}>
     */
    public function allowedRootsStatus(): array
    {
        $out = [];
        foreach ($this->allowedRoots as $root) {
            $absolute = $this->absolutize($root);
            $out[] = [
                'path' => $absolute,
                'writable' => is_dir($absolute) && is_writable($absolute),
            ];
        }

        return $out;
    }

    public function permissionDeniedHint(string $targetPath): string
    {
        $parent = is_dir($targetPath) ? $targetPath : dirname($targetPath);

        return 'Droits insuffisants pour écrire dans « ' . $parent . ' ». '
            . 'Sous Linux (nginx/php-fpm) : l’utilisateur PHP (souvent www-data) doit pouvoir '
            . 'créer le dossier projet sous FORGE_ALLOWED_ROOT (ex. chgrp www-data + chmod 2775). '
            . 'Vérifiez aussi COMPOSER_HOME writable (défaut storage/composer-home sous Forge).';
    }

    public function looksLikePermissionError(string $detail): bool
    {
        $lower = strtolower($detail);

        return str_contains($lower, 'permission denied')
            || str_contains($lower, 'access is denied')
            || str_contains($lower, 'failed to open stream: operation not permitted')
            || str_contains($lower, 'mkdir(): permission');
    }

    /**
     * @return array{0: string, 1: 'env'|'home'|'storage'|'temp'}
     */
    private function resolveComposerHome(): array
    {
        $fromEnv = getenv('COMPOSER_HOME');
        if (is_string($fromEnv) && trim($fromEnv) !== '') {
            return [rtrim($fromEnv, "/\\"), 'env'];
        }

        if ($this->isWindows()) {
            $appData = getenv('APPDATA');
            if (is_string($appData) && $appData !== '') {
                return [$appData . DIRECTORY_SEPARATOR . 'Composer', 'home'];
            }
            $userProfile = getenv('USERPROFILE');
            if (is_string($userProfile) && $userProfile !== '') {
                return [
                    $userProfile . DIRECTORY_SEPARATOR . 'AppData'
                    . DIRECTORY_SEPARATOR . 'Roaming' . DIRECTORY_SEPARATOR . 'Composer',
                    'home',
                ];
            }
        } else {
            $home = getenv('HOME');
            if (is_string($home) && $home !== '' && is_dir($home) && is_writable($home)) {
                return [$home . DIRECTORY_SEPARATOR . '.composer', 'home'];
            }
        }

        $storage = $this->storageComposerHome();
        if ($this->ensureWritableDir($storage)) {
            return [$storage, 'storage'];
        }

        return [sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'composer-home-forge', 'temp'];
    }

    private function storageComposerHome(): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'composer-home';
    }

    private function ensureWritableDir(string $path): bool
    {
        if (!is_dir($path) && !@mkdir($path, 0775, true) && !is_dir($path)) {
            return false;
        }

        return is_writable($path);
    }

    private function absolutize(string $path): string
    {
        if ($path !== '' && ($path[0] === '/' || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1)) {
            return $path;
        }

        return $this->basePath . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), '/\\');
    }

    private function isWindows(): bool
    {
        return DIRECTORY_SEPARATOR === '\\';
    }
}
