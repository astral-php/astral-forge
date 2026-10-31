<?php

declare(strict_types=1);

namespace App\Forge\Resolver;

use App\Forge\Blueprint\Blueprint;
use App\Forge\Exception\InvalidBlueprintException;

/**
 * Contrôle compatibilité cible / packages (refus core ^1).
 */
final class DependencyResolver
{
    public function assertCompatible(Blueprint $blueprint): void
    {
        if ($blueprint->target !== 'astral-starter-2.1') {
            throw new InvalidBlueprintException('Cible non compatible avec Forge 0.1.');
        }

        foreach ($blueprint->packages as $package) {
            if (preg_match('/astral-core[^\\s]*\\^?1\\b/', $package) === 1) {
                throw new InvalidBlueprintException('Package incompatible : astral-core ^1.');
            }
        }
    }
}
