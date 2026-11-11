<?php

declare(strict_types=1);

namespace App\Forge\Install;

use App\Forge\Runtime\PhpBinaryProbe;

/**
 * Génère un .env local opérationnel (install from scratch Win/Linux).
 *
 * APP_ENV=production → assets Vite /build sans serveur :5173.
 */
final class EnvGenerator
{
    public function __construct(
        private readonly string $basePath,
        private readonly ?bool $windows = null,
    ) {
    }

    public function isWindows(): bool
    {
        return $this->windows ?? (DIRECTORY_SEPARATOR === '\\');
    }

    /**
     * @return array{
     *     written: bool,
     *     path: string,
     *     skipped: bool,
     *     php_bin: string,
     *     composer_bin: string,
     *     allowed_root: string,
     *     messages: list<string>
     * }
     */
    public function generate(bool $force = false): array
    {
        $envPath = $this->basePath . DIRECTORY_SEPARATOR . '.env';
        $messages = [];

        if (is_file($envPath) && !$force) {
            return [
                'written' => false,
                'path' => $envPath,
                'skipped' => true,
                'php_bin' => '',
                'composer_bin' => '',
                'allowed_root' => '',
                'messages' => ['.env existe déjà — aucun changement (utilisez --force pour régénérer).'],
            ];
        }

        $phpBin = $this->detectPhpBin();
        $composerBin = $this->detectComposerBin($phpBin);
        $allowedRoot = $this->detectAllowedRoot();
        $allowApache = $this->isWindows() ? '0' : '1';

        $lines = $this->buildEnvLines(
            phpBin: $phpBin,
            composerBin: $composerBin,
            allowedRoot: $allowedRoot,
            allowApacheComposer: $allowApache,
        );

        if (file_put_contents($envPath, implode("\n", $lines) . "\n") === false) {
            throw new \RuntimeException('Impossible d’écrire ' . $envPath);
        }

        $messages[] = 'Écrit : ' . $envPath;
        $messages[] = 'OS : ' . ($this->isWindows() ? 'Windows' : 'Linux/Unix');
        $messages[] = 'FORGE_PHP_BIN=' . ($phpBin !== '' ? $phpBin : '(auto)');
        $messages[] = 'FORGE_COMPOSER_BIN=' . ($composerBin !== '' ? $composerBin : '(auto)');
        $messages[] = 'FORGE_ALLOWED_ROOT=' . $allowedRoot;

        if ($phpBin === '') {
            $messages[] = 'Avertissement : PHP non détecté — renseignez FORGE_PHP_BIN ou PATH.';
        }
        if ($composerBin === '') {
            $messages[] = 'Avertissement : Composer non détecté — renseignez FORGE_COMPOSER_BIN.';
        }

        return [
            'written' => true,
            'path' => $envPath,
            'skipped' => false,
            'php_bin' => $phpBin,
            'composer_bin' => $composerBin,
            'allowed_root' => $allowedRoot,
            'messages' => $messages,
        ];
    }

    public function detectPhpBin(): string
    {
        $candidates = [];

        if ($this->isWindows()) {
            foreach ($this->laragonPhpCandidates() as $candidate) {
                if ($this->isRunnableFile($candidate)) {
                    $candidates[] = $candidate;
                }
            }
        }

        $binary = PHP_BINARY;
        if (is_string($binary) && $binary !== '' && $this->isRunnableFile($binary)) {
            $base = strtolower(basename($binary));
            // Sous Apache/FPM, PHP_BINARY peut être httpd — ignorer
            if (!str_contains($base, 'httpd') && !str_contains($base, 'apache') && !str_contains($base, 'nginx')) {
                $candidates[] = $binary;
            }
        }

        foreach (['php8.5', 'php8.4', 'php8.3', 'php'] as $cmd) {
            $resolved = $this->which($cmd);
            if ($resolved !== null) {
                $candidates[] = $resolved;
            }
        }

        if (!$this->isWindows()) {
            foreach (['/usr/bin/php8.5', '/usr/bin/php8.4', '/usr/bin/php8.3', '/usr/bin/php'] as $path) {
                if ($this->isRunnableFile($path)) {
                    $candidates[] = $path;
                }
            }
        }

        $candidates = array_values(array_unique($candidates));
        $withSsl = PhpBinaryProbe::firstWithOpenssl($candidates);
        if ($withSsl !== null) {
            return $withSsl;
        }

        return $candidates[0] ?? '';
    }

    public function detectComposerBin(string $phpBin = ''): string
    {
        if ($this->isWindows()) {
            foreach ($this->laragonRoots() as $root) {
                $laragonPhar = $root . '\\bin\\composer\\composer.phar';
                if (is_file($laragonPhar)) {
                    return $laragonPhar;
                }
            }
        }

        foreach (['/usr/local/bin/composer', '/usr/bin/composer'] as $path) {
            if ($this->isRunnableFile($path)) {
                return $path;
            }
        }

        $which = $this->which('composer');
        if ($which !== null && !$this->looksLikeWindowsMount($which)) {
            return $which;
        }

        // composer.phar à côté de PHP
        if ($phpBin !== '') {
            $dir = dirname($phpBin);
            foreach ([$dir . '/composer.phar', dirname($dir) . '/composer/composer.phar'] as $phar) {
                if (is_file($phar)) {
                    return $phar;
                }
            }
        }

        return '';
    }

    public function detectAllowedRoot(): string
    {
        return dirname($this->basePath);
    }

    /**
     * @return list<string>
     */
    private function buildEnvLines(
        string $phpBin,
        string $composerBin,
        string $allowedRoot,
        string $allowApacheComposer,
    ): array {
        $allowedRootEnv = $this->isWindows()
            ? str_replace('/', '\\', $allowedRoot)
            : $allowedRoot;

        return [
            '# ============================================================',
            '# Astral Forge — généré par `composer forge:env` (ne pas committer)',
            '# ============================================================',
            'APP_NAME="Astral Forge"',
            'APP_VERSION=0.6.2',
            'APP_ENV=production',
            'APP_DEBUG=true',
            'APP_TIMEZONE=Europe/Paris',
            'APP_CHARSET=UTF-8',
            'APP_BASE_URL=',
            'APP_URL=',
            '',
            '# Vite — laisser commenté en production locale (assets public/build)',
            '# VITE_DEV_SERVER=http://localhost:5173',
            '# VITE_BUILD_DIR=/build',
            '',
            'DB_DRIVER=sqlite',
            'DB_DATABASE=database/forge.sqlite',
            '',
            'AUTH_REGISTRATION=direct',
            '',
            'FORGE_ALLOWED_ROOT=' . $allowedRootEnv,
            '# FORGE_STARTER_PACKAGE=astral-php/astral-starter:^2.1',
            'FORGE_PHP_BIN=' . $phpBin,
            'FORGE_COMPOSER_BIN=' . $composerBin,
            'FORGE_ALLOW_APACHE_COMPOSER=' . $allowApacheComposer,
            '',
            'MAIL_DRIVER=mail',
            'MAIL_HOST=localhost',
            'MAIL_PORT=25',
            'MAIL_USERNAME=',
            'MAIL_PASSWORD=',
            'MAIL_ENCRYPTION=',
            'MAIL_FROM=noreply@localhost',
            'MAIL_FROM_NAME="Astral Forge"',
        ];
    }

    /**
     * @return list<string>
     */
    private function laragonRoots(): array
    {
        $roots = [];
        $env = getenv('LARAGON_ROOT');
        if (is_string($env) && $env !== '') {
            $roots[] = rtrim($env, "\\/");
        }
        foreach (['C:\\laragon', 'D:\\laragon'] as $root) {
            $roots[] = $root;
        }

        return array_values(array_unique(array_filter(
            $roots,
            static fn (string $root): bool => is_dir($root)
        )));
    }

    /**
     * @return list<string>
     */
    private function laragonPhpCandidates(): array
    {
        $out = [];
        foreach ($this->laragonRoots() as $laragon) {
            $root = $laragon . '\\bin\\php';
            if (!is_dir($root)) {
                continue;
            }
            foreach (glob($root . '\\php-8.*') ?: [] as $dir) {
                $exe = $dir . '\\php.exe';
                if (is_file($exe)) {
                    $out[] = $exe;
                }
            }
        }
        // Préférer 8.3+ en tri inverse
        rsort($out);

        return $out;
    }

    private function which(string $command): ?string
    {
        $cmd = $this->isWindows()
            ? 'where ' . escapeshellarg($command)
            : 'command -v ' . escapeshellarg($command);
        $out = [];
        $code = 0;
        @exec($cmd, $out, $code);
        if ($code !== 0 || $out === []) {
            return null;
        }
        $path = trim((string) $out[0]);

        return $path !== '' ? $path : null;
    }

    private function isRunnableFile(string $path): bool
    {
        return $path !== '' && is_file($path) && (is_executable($path) || $this->isWindows());
    }

    private function looksLikeWindowsMount(string $path): bool
    {
        return str_starts_with($path, '/mnt/c/')
            || str_starts_with($path, '/mnt/d/')
            || str_contains($path, '/laragon/bin/composer');
    }
}
