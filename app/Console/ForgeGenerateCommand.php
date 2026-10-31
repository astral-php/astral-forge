<?php

declare(strict_types=1);

namespace App\Console;

use App\Forge\Exception\GenerationException;
use App\Forge\Exception\InvalidBlueprintException;
use App\Forge\Exception\UnsafePathException;
use App\Forge\ProjectGenerationService;
use Core\Console\CommandInterface;
use Core\Console\Console;

/**
 * Usage : php bin/console forge:generate <blueprint.json> --dest=<chemin> --confirm
 *         php bin/console forge:generate <blueprint.json> --dest=<chemin> --dry-run
 */
final class ForgeGenerateCommand implements CommandInterface
{
    public function __construct(
        private readonly ProjectGenerationService $service,
    ) {
    }

    #[\Override]
    public function getName(): string
    {
        return 'forge:generate';
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Génère un projet Astral (blank) après confirmation.';
    }

    #[\Override]
    public function execute(array $args, Console $console): int
    {
        $blueprintPath = null;
        $dest = null;
        $confirm = false;
        $dryRun = false;

        foreach ($args as $arg) {
            if ($arg === '--confirm') {
                $confirm = true;
                continue;
            }
            if ($arg === '--dry-run') {
                $dryRun = true;
                continue;
            }
            if (str_starts_with($arg, '--dest=')) {
                $dest = substr($arg, 7);
                continue;
            }
            if (!str_starts_with($arg, '--') && $blueprintPath === null) {
                $blueprintPath = $arg;
            }
        }

        if ($blueprintPath === null || $dest === null || $dest === '') {
            $console->error('Usage : forge:generate <blueprint.json> --dest=<chemin> [--dry-run|--confirm]');

            return 1;
        }

        try {
            $raw = file_get_contents($blueprintPath);
            if ($raw === false) {
                throw new InvalidBlueprintException('Lecture blueprint impossible.');
            }
            /** @var array<string, mixed> $data */
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            $report = $this->service->generate($data, $dest, confirm: $confirm || $dryRun, dryRun: $dryRun);
        } catch (InvalidBlueprintException | UnsafePathException | GenerationException | \JsonException $e) {
            $console->error($e->getMessage());

            return 1;
        }

        if (!$report->success) {
            foreach ($report->errors as $error) {
                $console->error($error);
            }

            return 1;
        }

        if ($dryRun) {
            $console->success('Dry-run OK — aucune écriture.');
        } else {
            $console->success('Projet généré : ' . $report->destination);
        }

        foreach ($report->entries as $entry) {
            $console->writeln(sprintf(
                '  [%s] %s — %s',
                $entry['status'],
                $entry['operation'],
                $entry['message']
            ));
        }

        return 0;
    }
}
