<?php

declare(strict_types=1);

namespace App\Forge\Runtime;

use App\Forge\Exception\GenerationException;

/**
 * Résout PHP + Composer pour le process web/CLI (Laragon Windows inclus).
 *
 * Ordre PHP : FORGE_PHP_BIN → PHP_BINARY → php/php.exe sur PATH.
 * Ordre Composer : FORGE_COMPOSER_BIN → PATH → composer.phar voisin Laragon.
 * Invocation préférée : php + composer.phar (évite les shims .bat sous proc_open).
 */
final class ToolchainResolver
{
    public function __construct(
        private readonly string $phpBinConfig = '',
        private readonly string $composerBinConfig = '',
        private readonly ?bool $windows = null,
    ) {
    }

    public function isWindows(): bool
    {
        return $this->windows ?? (DIRECTORY_SEPARATOR === '\\');
    }

    /**
     * @throws GenerationException
     */
    public function resolve(): ToolchainResolution
    {
        $phpBin = $this->resolvePhpBin();
        [$prefix, $label, $bypassShell] = $this->resolveComposerPrefix($phpBin);

        return new ToolchainResolution($phpBin, $prefix, $label, $bypassShell);
    }

    /**
     * @return array{
     *     ok: bool,
     *     proc_open: bool,
     *     php: ?string,
     *     composer: ?string,
     *     bypass_shell: ?bool,
     *     command_preview: ?string,
     *     errors: list<string>
     * }
     */
    public function preflight(): array
    {
        $errors = [];
        $procOpen = function_exists('proc_open');
        if (!$procOpen) {
            $errors[] = 'proc_open indisponible — activez-le dans php.ini (disable_functions).';
        }

        $php = null;
        $composer = null;
        $bypass = null;
        $preview = null;

        try {
            $resolved = $this->resolve();
            $php = $resolved->phpBin;
            $composer = $resolved->composerLabel;
            $bypass = $resolved->bypassShell;
            $preview = $resolved->toArray()['command_preview'];

            if (!$this->looksRunnable($php)) {
                $errors[] = 'PHP introuvable ou non exécutable : ' . $php
                    . ' — définissez FORGE_PHP_BIN (ex. chemin absolu vers php.exe).';
            }

            foreach ($resolved->composerPrefix as $part) {
                if ($part === '' || str_starts_with($part, '-')) {
                    continue;
                }
                if (!$this->looksRunnable($part) && !$this->isPhar($part)) {
                    // Noms nus (composer) : OK si sur PATH — vérifiés à l’exécution
                    if ($this->isBareCommand($part)) {
                        continue;
                    }
                    $errors[] = 'Élément Composer introuvable : ' . $part
                        . ' — définissez FORGE_COMPOSER_BIN (composer.phar ou binaire).';
                }
            }
        } catch (GenerationException $e) {
            $errors[] = $e->getMessage();
        }

        return [
            'ok' => $procOpen && $errors === [],
            'proc_open' => $procOpen,
            'php' => $php,
            'composer' => $composer,
            'bypass_shell' => $bypass,
            'command_preview' => $preview,
            'errors' => $errors,
        ];
    }

    /**
     * @throws GenerationException
     */
    public function assertReady(): void
    {
        $status = $this->preflight();
        if ($status['ok']) {
            return;
        }

        throw new GenerationException(
            'Environnement Forge incomplet : ' . implode(' ', $status['errors'])
        );
    }

    private function resolvePhpBin(): string
    {
        $configured = trim($this->phpBinConfig);
        if ($configured !== '') {
            $ssl = PhpBinaryProbe::opensslStatus($configured);
            if ($ssl === 'yes' || $ssl === 'unknown') {
                // unknown = stub / non sondable → respecter la config (tests + bins custom)
                return $configured;
            }
            // FORGE_PHP_BIN sans openssl (ex. Laragon 8.5 nts) → bascule auto si un autre PHP OK
            $better = $this->findPhpWithOpenssl();
            if ($better !== null) {
                return $better;
            }

            return $configured;
        }

        $better = $this->findPhpWithOpenssl();
        if ($better !== null) {
            return $better;
        }

        // Sous Apache Windows, PHP_BINARY vaut souvent httpd.exe — inutilisable pour CLI.
        if (defined('PHP_BINARY') && is_string(PHP_BINARY) && PHP_BINARY !== '' && $this->isUsablePhpCliBinary(PHP_BINARY)) {
            return PHP_BINARY;
        }

        $fromPath = $this->isWindows()
            ? ($this->findInPath('php.exe') ?? $this->findInPath('php'))
            : $this->findInPath('php');
        if ($fromPath !== null) {
            return $fromPath;
        }

        if (defined('PHP_BINARY') && is_string(PHP_BINARY) && PHP_BINARY !== '') {
            $laragonPhp = $this->guessLaragonPhpNearApache(PHP_BINARY);
            if ($laragonPhp !== null) {
                return $laragonPhp;
            }
        }

        return $this->isWindows() ? 'php.exe' : 'php';
    }

    private function findPhpWithOpenssl(): ?string
    {
        $candidates = [];
        if ($this->isWindows()) {
            $root = 'D:\\laragon\\bin\\php';
            if (is_dir($root)) {
                foreach (glob($root . '\\php-8.*\\php.exe') ?: [] as $exe) {
                    $candidates[] = $exe;
                }
                rsort($candidates);
            }
        }

        if (defined('PHP_BINARY') && is_string(PHP_BINARY) && $this->isUsablePhpCliBinary(PHP_BINARY)) {
            $candidates[] = PHP_BINARY;
        }

        $fromPath = $this->isWindows()
            ? ($this->findInPath('php.exe') ?? $this->findInPath('php'))
            : $this->findInPath('php');
        if ($fromPath !== null) {
            $candidates[] = $fromPath;
        }

        return PhpBinaryProbe::firstWithOpenssl(array_values(array_unique($candidates)));
    }

    /**
     * True si le binaire peut lancer un script CLI (pas httpd / apache).
     */
    private function isUsablePhpCliBinary(string $path): bool
    {
        $base = strtolower(basename(str_replace('\\', '/', $path)));

        if (
            str_contains($base, 'httpd')
            || str_contains($base, 'apache')
            || $base === 'nginx.exe'
            || $base === 'nginx'
        ) {
            return false;
        }

        // php.exe, php, php8.3, php-cgi.exe (cgi moins idéal mais scriptable)
        if (!str_starts_with($base, 'php')) {
            return false;
        }

        if ($this->isBareCommand($path)) {
            return true;
        }

        return is_file($path);
    }

    /**
     * Depuis …/bin/apache/httpd-…/bin/httpd.exe vers …/bin/php/php-8.x/php.exe (Laragon).
     */
    private function guessLaragonPhpNearApache(string $apacheBinary): ?string
    {
        if ($this->isBareCommand($apacheBinary) || !is_file($apacheBinary)) {
            return null;
        }

        // httpd.exe → bin → httpd-x → apache → bin (Laragon)
        $laragonBin = dirname($apacheBinary, 4);
        $phpRoot = $laragonBin . DIRECTORY_SEPARATOR . 'php';
        if (!is_dir($phpRoot)) {
            return null;
        }

        $candidates = glob($phpRoot . DIRECTORY_SEPARATOR . 'php-*' . DIRECTORY_SEPARATOR . 'php.exe') ?: [];
        if ($candidates === []) {
            $candidates = glob($phpRoot . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'php.exe') ?: [];
        }
        sort($candidates);

        $last = end($candidates);

        return is_string($last) && is_file($last) ? $last : null;
    }

    /**
     * @return array{0: list<string>, 1: string, 2: bool}
     *
     * @throws GenerationException
     */
    private function resolveComposerPrefix(string $phpBin): array
    {
        $configured = trim($this->composerBinConfig);

        if ($configured !== '') {
            return $this->prefixFromConfigured($configured, $phpBin);
        }

        $fromPath = $this->findComposerOnPath();
        if ($fromPath !== null) {
            return $this->prefixFromLocated($fromPath, $phpBin);
        }

        $laragonPhar = $this->guessLaragonComposerPhar($phpBin);
        if ($laragonPhar !== null) {
            return [[$phpBin, $laragonPhar], $laragonPhar, true];
        }

        $pathHint = $this->isWindows()
            ? 'Ajoutez Laragon au PATH du service Apache, ou FORGE_COMPOSER_BIN=D:\\laragon\\bin\\composer\\composer.phar'
            : 'Installez Composer sur le PATH, ou définissez FORGE_COMPOSER_BIN=/usr/local/bin/composer';

        throw new GenerationException(
            'Composer introuvable (PATH + voisin Laragon). ' . $pathHint
        );
    }

    /**
     * @return array{0: list<string>, 1: string, 2: bool}
     */
    private function prefixFromConfigured(string $configured, string $phpBin): array
    {
        if ($this->isPhar($configured)) {
            return [[$phpBin, $configured], $configured, true];
        }

        if ($this->isWindowsShim($configured)) {
            $sibling = $this->siblingPhar($configured);
            if ($sibling !== null) {
                return [[$phpBin, $sibling], $sibling, true];
            }

            // Dernier recours : cmd /C pour .bat (bypass_shell false)
            return [['cmd.exe', '/C', $configured], $configured, false];
        }

        $asPath = $configured;
        if (!$this->isBareCommand($configured) && is_file($configured)) {
            $sibling = $this->siblingPhar($configured);
            if ($sibling !== null) {
                return [[$phpBin, $sibling], $sibling, true];
            }
        }

        // Binaire / script Composer (Linux shebang ou nom nu "composer")
        return [[$asPath], $asPath, true];
    }

    /**
     * @return array{0: list<string>, 1: string, 2: bool}
     */
    private function prefixFromLocated(string $located, string $phpBin): array
    {
        if ($this->isPhar($located)) {
            return [[$phpBin, $located], $located, true];
        }

        if ($this->isWindowsShim($located)) {
            $sibling = $this->siblingPhar($located);
            if ($sibling !== null) {
                return [[$phpBin, $sibling], $sibling, true];
            }

            return [['cmd.exe', '/C', $located], $located, false];
        }

        // Fichier "composer" sans extension (script bash Laragon) → préférer .phar voisin
        $sibling = $this->siblingPhar($located);
        if ($sibling !== null) {
            return [[$phpBin, $sibling], $sibling, true];
        }

        return [[$located], $located, true];
    }

    private function findComposerOnPath(): ?string
    {
        $names = $this->isWindows()
            ? ['composer.phar', 'composer.bat', 'composer.cmd', 'composer.exe', 'composer']
            : ['composer', 'composer.phar'];

        foreach ($names as $name) {
            $found = $this->findInPath($name);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    private function findInPath(string $basename): ?string
    {
        $path = getenv('PATH');
        if (!is_string($path) || $path === '') {
            return null;
        }

        foreach (explode(PATH_SEPARATOR, $path) as $dir) {
            $dir = trim($dir, " \t\"'");
            if ($dir === '') {
                continue;
            }
            $full = $dir . DIRECTORY_SEPARATOR . $basename;
            if (is_file($full)) {
                return $full;
            }
        }

        return null;
    }

    private function guessLaragonComposerPhar(string $phpBin): ?string
    {
        if ($phpBin === '' || $this->isBareCommand($phpBin)) {
            return null;
        }

        $phpDir = dirname($phpBin);
        $candidates = [
            // .../bin/php/php-8.3.x → .../bin/composer/composer.phar
            dirname($phpDir, 2) . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'composer.phar',
            dirname($phpDir) . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'composer.phar',
            $phpDir . DIRECTORY_SEPARATOR . 'composer.phar',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function siblingPhar(string $path): ?string
    {
        if ($this->isBareCommand($path)) {
            return null;
        }

        $phar = dirname($path) . DIRECTORY_SEPARATOR . 'composer.phar';

        return is_file($phar) ? $phar : null;
    }

    private function isPhar(string $path): bool
    {
        return str_ends_with(strtolower($path), '.phar');
    }

    private function isWindowsShim(string $path): bool
    {
        $lower = strtolower($path);

        return str_ends_with($lower, '.bat') || str_ends_with($lower, '.cmd');
    }

    private function isBareCommand(string $path): bool
    {
        return !str_contains($path, '/') && !str_contains($path, '\\') && !str_contains($path, ':');
    }

    private function looksRunnable(string $path): bool
    {
        if ($this->isBareCommand($path)) {
            return $this->findInPath($path) !== null
                || ($this->isWindows() && $this->findInPath($path . '.exe') !== null);
        }

        return is_file($path);
    }
}
