<?php

declare(strict_types=1);

/**
 * Configuration moteur Astral Forge.
 *
 * @return array{
 *     allowed_roots: list<string>,
 *     starter_package: string,
 *     composer_bin: string,
 *     default_recipe: string,
 *     supported_recipes: list<string>,
 *     supported_targets: list<string>
 * }
 */
return [
    // Racines autorisées pour les projets générés (chemins absolus ou relatifs à BASE_PATH).
    'allowed_roots' => array_values(array_filter([
        $_ENV['FORGE_ALLOWED_ROOT'] ?? dirname(__DIR__, 2),
        sys_get_temp_dir(),
    ])),

    'starter_package' => $_ENV['FORGE_STARTER_PACKAGE'] ?? 'astral-php/astral-starter:^2.1',

    'composer_bin' => $_ENV['FORGE_COMPOSER_BIN'] ?? 'composer',

    'default_recipe' => 'blank',

    'supported_recipes' => [
        'blank',
        'blog-lite',
        'api-json',
    ],

    'supported_targets' => [
        'astral-starter-2.1',
    ],
];
