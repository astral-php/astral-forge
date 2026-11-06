<?php

declare(strict_types=1);

namespace App\Forge\Runtime;

/**
 * Binaires résolus pour invoquer Composer de façon portable (Win + Linux).
 */
final class ToolchainResolution
{
    /**
     * @param list<string> $composerPrefix Arguments avant create-project
     *                                     (ex. [php.exe, composer.phar] ou [composer])
     */
    public function __construct(
        public readonly string $phpBin,
        public readonly array $composerPrefix,
        public readonly string $composerLabel,
        public readonly bool $bypassShell,
    ) {
    }

    /**
     * @return list<string>
     */
    public function createProjectCommand(string $package, string $directory): array
    {
        return [
            ...$this->composerPrefix,
            'create-project',
            $package,
            $directory,
            '--no-interaction',
            '--prefer-dist',
        ];
    }

    /**
     * @return array{php: string, composer: string, bypass_shell: bool, command_preview: string}
     */
    public function toArray(): array
    {
        return [
            'php' => $this->phpBin,
            'composer' => $this->composerLabel,
            'bypass_shell' => $this->bypassShell,
            'command_preview' => implode(' ', [
                ...$this->composerPrefix,
                'create-project',
                '<package>',
                '<dir>',
            ]),
        ];
    }
}
