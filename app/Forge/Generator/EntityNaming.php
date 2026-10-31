<?php

declare(strict_types=1);

namespace App\Forge\Generator;

final class EntityNaming
{
    public static function toSnakePlural(string $pascal): string
    {
        $snake = strtolower((string) preg_replace('/[A-Z]/', '_$0', lcfirst($pascal)));
        if (str_ends_with($snake, 'y')) {
            return substr($snake, 0, -1) . 'ies';
        }
        if (str_ends_with($snake, 's') || str_ends_with($snake, 'x')) {
            return $snake . 'es';
        }

        return $snake . 's';
    }

    public static function toViewFolder(string $pascal): string
    {
        return strtolower((string) preg_replace('/[A-Z]/', '-$0', lcfirst($pascal)));
    }
}
