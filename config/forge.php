<?php

declare(strict_types=1);

/**
 * Configuration moteur Astral Forge.
 *
 * @return array{
 *     allowed_roots: list<string>,
 *     starter_package: string,
 *     php_bin: string,
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

    // Vide = auto (PHP_BINARY puis php sur PATH). Ex. Laragon : D:\laragon\bin\php\php-8.3.x\php.exe
    'php_bin' => $_ENV['FORGE_PHP_BIN'] ?? '',

    // Vide = auto (PATH + composer.phar Laragon). Préférer un .phar : D:\laragon\bin\composer\composer.phar
    'composer_bin' => $_ENV['FORGE_COMPOSER_BIN'] ?? '',

    // Apache Windows : create-project via proc_open casse souvent mpm_winnt — CLI par défaut.
    'allow_apache_composer' => filter_var(
        $_ENV['FORGE_ALLOW_APACHE_COMPOSER'] ?? '0',
        FILTER_VALIDATE_BOOL
    ),

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
