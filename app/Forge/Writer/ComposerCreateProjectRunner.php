<?php

declare(strict_types=1);

namespace App\Forge\Writer;

use App\Forge\Exception\GenerationException;

/**
 * Exécute composer create-project de façon non interactive.
 */
final class ComposerCreateProjectRunner
{
    public function __construct(
        private readonly string $composerBin = 'composer',
    ) {
    }

    public function run(string $package, string $directory): void
    {
        if (!function_exists('proc_open')) {
            throw new GenerationException('proc_open indisponible — impossible d’appeler Composer.');
        }

        $cmd = [
            $this->composerBin,
            'create-project',
            $package,
            $directory,
            '--no-interaction',
            '--prefer-dist',
        ];

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes, null, null, ['bypass_shell' => true]);
        if (!is_resource($process)) {
            throw new GenerationException('Échec du lancement de Composer.');
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]) ?: '';
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($process);

        if ($code !== 0) {
            $detail = trim($stderr !== '' ? $stderr : $stdout);
            throw new GenerationException(
                'composer create-project a échoué (code ' . $code . ')'
                . ($detail !== '' ? ' : ' . $detail : '.')
            );
        }
    }
}
