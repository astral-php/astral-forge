<?php

declare(strict_types=1);

namespace App\Forge\Generator;

final class EntityNaming
{
    public static function toSnakePlural(string $pascal): string
    {
        $irregular = [
            'news' => 'news',
            'person' => 'people',
            'child' => 'children',
        ];

        $snake = strtolower((string) preg_replace('/[A-Z]/', '_$0', lcfirst($pascal)));
        if (isset($irregular[$snake])) {
            return $irregular[$snake];
        }
        if (str_ends_with($snake, 'y') && !preg_match('/[aeiou]y$/', $snake)) {
            return substr($snake, 0, -1) . 'ies';
        }
        if (str_ends_with($snake, 's') || str_ends_with($snake, 'x') || str_ends_with($snake, 'ch') || str_ends_with($snake, 'sh')) {
            return $snake . 'es';
        }

        return $snake . 's';
    }

    public static function toViewFolder(string $pascal): string
    {
        return strtolower((string) preg_replace('/[A-Z]/', '-$0', lcfirst($pascal)));
    }
}
