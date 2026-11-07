<?php

declare(strict_types=1);

namespace App\Forge\Atelier;

use App\Forge\Exception\GenerationException;
use App\Forge\Job\CliJob;
use App\Forge\Job\CliJobStore;
use App\Forge\Job\DetachedCliLauncher;
use App\Forge\Runtime\ToolchainResolver;
use App\Forge\Support\Utf8;
use App\Forge\Writer\ComposerCreateProjectRunner;

/**
 * Exécute `php bin/console …` dans un projet enregistré (sync ou job async).
 */
final class ProjectConsoleRunner
{
    public function __construct(
        private readonly ToolchainResolver $toolchain = new ToolchainResolver(),
        private readonly CliJobStore $jobStore = new CliJobStore(),
        private readonly DetachedCliLauncher $launcher = new DetachedCliLauncher(),
        private readonly ComposerCreateProjectRunner $composerRunner = new ComposerCreateProjectRunner(),
    ) {
    }

    /**
     * @param list<string> $argv
     *
     * @return array{mode: 'sync', ok: bool, output: string, error: ?string}
     *         |array{mode: 'async', job: CliJob}
     */
    public function run(string $projectPath, string $type, array $argv): array
    {
        $console = $projectPath . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'console';
        if (!is_file($console)) {
            throw new GenerationException('bin/console introuvable dans le projet.');
        }

        $runtime = $this->composerRunner->environmentStatus();
        $syncOk = ($runtime['web_generate_allowed'] ?? true) === true;

        if ($syncOk) {
            return $this->runSync($projectPath, $argv);
        }

        $job = $this->jobStore->create($type, $projectPath, $argv);
        $this->launcher->launch($job->id);

        return ['mode' => 'async', 'job' => $job];
    }

    public function getJob(string $id): ?CliJob
    {
        return $this->jobStore->get($id);
    }

    /**
     * @param list<string> $argv
     *
     * @return array{mode: 'sync', ok: bool, output: string, error: ?string}
     */
    public function runSync(string $projectPath, array $argv): array
    {
        $php = $this->toolchain->resolve()->phpBin;
        $console = $projectPath . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'console';
        $cmd = array_merge([$php, $console], $argv);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = @proc_open(
            $cmd,
            $descriptors,
            $pipes,
            $projectPath,
            null,
            ['bypass_shell' => true],
        );
        if (!is_resource($process)) {
            throw new GenerationException('Impossible de lancer bin/console (proc_open).');
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]) ?: '';
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($process);

        $output = self::cleanConsoleOutput(trim($stdout . ($stderr !== '' ? "\n" . $stderr : '')));
        $ok = $code === 0;

        return [
            'mode' => 'sync',
            'ok' => $ok,
            'output' => $output,
            'error' => $ok ? null : ($output !== '' ? $output : 'Commande console échouée (code ' . $code . ').'),
        ];
    }

    public static function cleanConsoleOutput(string $output): string
    {
        $output = Utf8::stringify($output);
        // ESC + […m  ou séquences littérales "\033[…m" parfois capturées
        $stripped = preg_replace("/\x1B\\[[0-9;]*m|\\\\033\\[[0-9;]*m/", '', $output);

        return is_string($stripped) ? trim($stripped) : trim($output);
    }
}
