<?php

declare(strict_types=1);

namespace App\Forge\Job;

use App\Forge\Exception\GenerationException;
use App\Forge\Runtime\ToolchainResolver;

/**
 * Lance `bin/forge-job` hors du process Apache (start /B sous Windows).
 */
final class DetachedJobLauncher
{
    public function __construct(
        private readonly ToolchainResolver $toolchain = new ToolchainResolver(),
    ) {
    }

    /**
     * @throws GenerationException
     */
    public function launch(string $jobId): void
    {
        $php = $this->toolchain->resolve()->phpBin;
        $script = BASE_PATH . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'forge-job';
        if (!is_file($script)) {
            throw new GenerationException('Script bin/forge-job introuvable.');
        }

        if ($this->toolchain->isWindows()) {
            $this->launchWindows($php, $script, $jobId);

            return;
        }

        $this->launchUnix($php, $script, $jobId);
    }

    private function launchWindows(string $php, string $script, string $jobId): void
    {
        $dir = BASE_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'forge-jobs';
        $wrapper = $dir . DIRECTORY_SEPARATOR . $jobId . '.launch.cmd';

        $appData = getenv('APPDATA');
        $userProfile = getenv('USERPROFILE');
        if ((!is_string($appData) || $appData === '') && is_string($userProfile) && $userProfile !== '') {
            $appData = $userProfile . '\\AppData\\Roaming';
        }
        if (!is_string($appData) || $appData === '') {
            $appData = sys_get_temp_dir();
        }
        $composerHome = getenv('COMPOSER_HOME') ?: ($appData . '\\Composer');
        $path = getenv('PATH') ?: '';
        $log = $dir . DIRECTORY_SEPARATOR . $jobId . '.log';

        $lines = [
            '@echo off',
            'set "APPDATA=' . $this->batEscape($appData) . '"',
            'set "COMPOSER_HOME=' . $this->batEscape((string) $composerHome) . '"',
            'set "COMPOSER_NO_INTERACTION=1"',
            'set "COMPOSER_DISABLE_XDEBUG=1"',
            'set "PATH=' . $this->batEscape($path) . '"',
            'cd /d "' . $this->batEscape(BASE_PATH) . '"',
            '"' . $this->batEscape($php) . '" "' . $this->batEscape($script) . '" ' . $jobId
                . ' >> "' . $this->batEscape($log) . '" 2>&1',
        ];
        if (file_put_contents($wrapper, implode("\r\n", $lines) . "\r\n") === false) {
            throw new GenerationException('Impossible d’écrire le lanceur job Windows.');
        }

        // start /B détache du worker Apache — ne pas attendre la fin
        $cmd = 'cmd /c start /B "" "' . $wrapper . '"';
        $handle = @popen($cmd, 'r');
        if (!is_resource($handle)) {
            throw new GenerationException('Impossible de démarrer le job en arrière-plan (Windows).');
        }
        pclose($handle);
    }

    private function launchUnix(string $php, string $script, string $jobId): void
    {
        $log = BASE_PATH . '/storage/forge-jobs/' . $jobId . '.log';
        $cmd = sprintf(
            'nohup %s %s %s >> %s 2>&1 &',
            escapeshellarg($php),
            escapeshellarg($script),
            escapeshellarg($jobId),
            escapeshellarg($log),
        );
        $handle = @popen($cmd, 'r');
        if (!is_resource($handle)) {
            throw new GenerationException('Impossible de démarrer le job en arrière-plan (Unix).');
        }
        pclose($handle);
    }

    private function batEscape(string $value): string
    {
        return str_replace(['%', '"'], ['%%', ''], $value);
    }
}
