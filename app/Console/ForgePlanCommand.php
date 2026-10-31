<?php

declare(strict_types=1);

namespace App\Console;

use App\Forge\Exception\InvalidBlueprintException;
use App\Forge\Exception\UnsafePathException;
use App\Forge\ProjectGenerationService;
use Core\Console\CommandInterface;
use Core\Console\Console;

/**
 * Usage : php bin/console forge:plan <blueprint.json> --dest=<chemin>
 */
final class ForgePlanCommand implements CommandInterface
{
    public function __construct(
        private readonly ProjectGenerationService $service,
    ) {
    }

    #[\Override]
    public function getName(): string
    {
        return 'forge:plan';
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Affiche le plan de génération (sans écrire).';
    }

    #[\Override]
    public function execute(array $args, Console $console): int
    {
        [$blueprintPath, $dest] = $this->parseArgs($args, $console);
        if ($blueprintPath === null || $dest === null) {
            return 1;
        }

        try {
            $data = $this->loadBlueprint($blueprintPath);
            $plan = $this->service->plan($data, $dest);
        } catch (InvalidBlueprintException | UnsafePathException | \JsonException $e) {
            $console->error($e->getMessage());

            return 1;
        }

        $console->success('Plan déterministe — aucune écriture.');
        $console->writeln('Destination : ' . $plan->destination);
        $console->writeln('Recette     : ' . $plan->blueprint->recipe);
        $console->writeln('Projet      : ' . $plan->blueprint->projectName);
        $console->writeln('');
        foreach ($plan->operations as $i => $op) {
            $n = $i + 1;
            $console->writeln(sprintf(
                '  %d. [%s] %s',
                $n,
                $op->type,
                $op->description !== '' ? $op->description : $op->path
            ));
        }

        return 0;
    }

    /**
     * @param list<string> $args
     * @return array{0: ?string, 1: ?string}
     */
    private function parseArgs(array $args, Console $console): array
    {
        $blueprintPath = null;
        $dest = null;

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--dest=')) {
                $dest = substr($arg, 7);
                continue;
            }
            if (!str_starts_with($arg, '--') && $blueprintPath === null) {
                $blueprintPath = $arg;
            }
        }

        if ($blueprintPath === null || $dest === null || $dest === '') {
            $console->error('Usage : forge:plan <blueprint.json> --dest=<chemin>');

            return [null, null];
        }

        return [$blueprintPath, $dest];
    }

    /**
     * @return array<string, mixed>
     */
    private function loadBlueprint(string $path): array
    {
        if (!is_file($path)) {
            throw new InvalidBlueprintException('Fichier blueprint introuvable : ' . $path);
        }
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new InvalidBlueprintException('Lecture blueprint impossible : ' . $path);
        }
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new InvalidBlueprintException('Le blueprint doit être un objet JSON.');
        }

        /** @var array<string, mixed> $data */
        return $data;
    }
}
