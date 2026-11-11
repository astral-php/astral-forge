<?php

declare(strict_types=1);

namespace App\Forge\Writer;

use App\Forge\Exception\GenerationException;
use App\Forge\Runtime\RuntimeHealth;
use App\Forge\Runtime\ToolchainResolver;
use App\Forge\Support\Utf8;

/**
 * Exécute composer create-project de façon non interactive (Win + Linux).
 *
 * Sous Apache Windows (mpm_winnt / mod_php), proc_open casse souvent le worker :
 * désactivé par défaut — préférer CLI (`php bin/forge generate …`).
 */
final class ComposerCreateProjectRunner
{
    private readonly RuntimeHealth $health;

    public function __construct(
        private readonly ToolchainResolver $toolchain = new ToolchainResolver(),
        private readonly bool $allowApacheComposer = false,
        ?RuntimeHealth $health = null,
    ) {
        $this->health = $health ?? new RuntimeHealth(
            defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3),
        );
    }

    public function toolchain(): ToolchainResolver
    {
        return $this->toolchain;
    }

    /**
     * @throws GenerationException
     */
    public function assertReady(): void
    {
        $this->toolchain->assertReady();
        $this->assertWebRuntimeAllowed();
    }

    /**
     * @return array{
     *     ok: bool,
     *     proc_open: bool,
     *     php: ?string,
     *     composer: ?string,
     *     bypass_shell: ?bool,
     *     command_preview: ?string,
     *     sapi: string,
     *     web_generate_allowed: bool,
     *     web_generate_hint: ?string,
     *     composer_home: array{path: string, writable: bool, source: string, hint: ?string},
     *     allowed_roots: list<array{path: string, writable: bool}>,
     *     errors: list<string>
     * }
     */
    public function environmentStatus(): array
    {
        $status = $this->toolchain->preflight();
        $allowed = $this->isWebGenerateAllowed();
        $hint = $allowed
            ? null
            : 'Apache Windows (Laragon) : génération web désactivée (mpm_winnt). '
                . 'Utilisez la CLI après le plan, ou `composer serve`, ou FORGE_ALLOW_APACHE_COMPOSER=1.';

        $composerHome = $this->health->composerHomeStatus();
        if (!$composerHome['writable'] && is_string($composerHome['hint'])) {
            $status['errors'][] = $composerHome['hint'];
            $status['ok'] = false;
        }

        $roots = $this->health->allowedRootsStatus();
        $writableRoot = false;
        foreach ($roots as $root) {
            if ($root['writable']) {
                $writableRoot = true;
                break;
            }
        }
        if ($roots !== [] && !$writableRoot) {
            $status['errors'][] = 'Aucune racine FORGE_ALLOWED_ROOT writable pour l’utilisateur PHP. '
                . 'Sous Linux : chgrp/chmod sur le dossier projets, ou changez FORGE_ALLOWED_ROOT.';
            $status['ok'] = false;
        }

        return [
            ...$status,
            'sapi' => PHP_SAPI,
            'web_generate_allowed' => $allowed,
            'web_generate_hint' => $hint,
            'composer_home' => $composerHome,
            'allowed_roots' => $roots,
        ];
    }

    public function run(string $package, string $directory): void
    {
        if (!function_exists('proc_open')) {
            throw new GenerationException('proc_open indisponible — impossible d’appeler Composer.');
        }

        $this->assertReady();

        @set_time_limit(0);
        @ini_set('max_execution_time', '0');

        $resolved = $this->toolchain->resolve();
        $cmd = $resolved->createProjectCommand($package, $directory);

        $stdoutFile = tempnam(sys_get_temp_dir(), 'forge-out-');
        $stderrFile = tempnam(sys_get_temp_dir(), 'forge-err-');
        if ($stdoutFile === false || $stderrFile === false) {
            throw new GenerationException('Impossible de créer les fichiers temporaires Composer.');
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['file', $stdoutFile, 'w'],
            2 => ['file', $stderrFile, 'w'],
        ];

        $cwd = is_dir(dirname($directory)) ? dirname($directory) : null;
        $env = $this->buildEnv();
        $options = ['bypass_shell' => $resolved->bypassShell];

        $process = @proc_open($cmd, $descriptors, $pipes, $cwd, $env, $options);

        if (!is_resource($process)) {
            $this->cleanupTemp($stdoutFile, $stderrFile);
            throw new GenerationException(
                'Échec du lancement de Composer (`'
                . implode(' ', $resolved->composerPrefix)
                . '`). Vérifiez FORGE_PHP_BIN / FORGE_COMPOSER_BIN et le PATH du serveur web.'
            );
        }

        fclose($pipes[0]);
        $code = proc_close($process);

        $stdout = Utf8::stringify((string) @file_get_contents($stdoutFile));
        $stderr = Utf8::stringify((string) @file_get_contents($stderrFile));
        $this->cleanupTemp($stdoutFile, $stderrFile);

        if ($code !== 0) {
            $detail = trim($stderr !== '' ? $stderr : $stdout);
            if ($this->looksLikeApacheCrash($detail)) {
                throw new GenerationException(
                    'Apache Windows a interrompu Composer (mpm_winnt). '
                    . 'Relancez en CLI : php bin/forge generate <blueprint.json> --dest="'
                    . $directory . '" --confirm'
                );
            }
            if (strlen($detail) > 4000) {
                $detail = substr($detail, 0, 4000) . '…';
            }
            $message = 'composer create-project a échoué (code ' . $code . ')'
                . ($detail !== '' ? ' : ' . $detail : '.');
            if ($this->health->looksLikePermissionError($detail)) {
                $message .= ' — ' . $this->health->permissionDeniedHint($directory);
            }
            throw new GenerationException($message);
        }
    }

    /**
     * @throws GenerationException
     */
    private function assertWebRuntimeAllowed(): void
    {
        if ($this->isWebGenerateAllowed()) {
            return;
        }

        throw new GenerationException(
            'Génération web désactivée sous Apache Windows (Laragon / mpm_winnt + proc_open). '
            . 'Utilisez la CLI : php bin/forge generate <blueprint.json> --dest=… --confirm '
            . '(plan web OK). Contournement expérimental : FORGE_ALLOW_APACHE_COMPOSER=1.'
        );
    }

    private function isWebGenerateAllowed(): bool
    {
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'cli-server' || PHP_SAPI === 'phpdbg') {
            return true;
        }

        if (!$this->toolchain->isWindows()) {
            return true;
        }

        // apache2handler / litespeed sous Windows : risque mpm_winnt
        if ($this->allowApacheComposer) {
            return true;
        }

        return !str_contains(strtolower(PHP_SAPI), 'apache')
            && PHP_SAPI !== 'litespeed';
    }

    private function looksLikeApacheCrash(string $detail): bool
    {
        return str_contains($detail, 'mpm_winnt')
            || str_contains($detail, 'AH02965')
            || str_contains($detail, 'canal de communication')
            || str_contains($detail, 'Unable to retrieve my generation');
    }

    /**
     * Environnement pour Composer (PATH + COMPOSER_HOME writable ; sans vars Apache inutiles).
     *
     * @return array<string, string>
     */
    private function buildEnv(): array
    {
        $path = getenv('PATH');
        $userProfile = getenv('USERPROFILE');
        $home = getenv('HOME');
        $appData = getenv('APPDATA');
        $localAppData = getenv('LOCALAPPDATA');
        $composerHome = $this->health->resolveComposerHomePath();

        if ((!is_string($appData) || $appData === '') && is_string($userProfile) && $userProfile !== '') {
            $appData = $userProfile . DIRECTORY_SEPARATOR . 'AppData' . DIRECTORY_SEPARATOR . 'Roaming';
        }

        $env = [
            'PATH' => is_string($path) ? $path : '',
            'SystemRoot' => (string) (getenv('SystemRoot') ?: 'C:\\Windows'),
            'TEMP' => sys_get_temp_dir(),
            'TMP' => sys_get_temp_dir(),
            'COMPOSER_DISABLE_XDEBUG' => '1',
            'COMPOSER_NO_INTERACTION' => '1',
            'COMPOSER_HOME' => $composerHome,
            'HOME' => (string) ($home ?: ($userProfile ?: sys_get_temp_dir())),
        ];

        if (is_string($userProfile) && $userProfile !== '') {
            $env['USERPROFILE'] = $userProfile;
        }
        if (is_string($appData) && $appData !== '') {
            $env['APPDATA'] = $appData;
        }
        if (is_string($localAppData) && $localAppData !== '') {
            $env['LOCALAPPDATA'] = $localAppData;
        }

        return $env;
    }

    private function cleanupTemp(string ...$files): void
    {
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }
}
