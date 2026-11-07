<?php

declare(strict_types=1);

/**
 * @return array<string, mixed>
 */
return [
    'name'     => $_ENV['APP_NAME']     ?? 'Astral Forge',
    'version'  => $_ENV['APP_VERSION']  ?? '0.5.0',
    'env'      => $_ENV['APP_ENV']      ?? 'production',
    'debug'    => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'UTC',
    'charset'  => $_ENV['APP_CHARSET']  ?? 'UTF-8',
    'base_url' => $_ENV['APP_BASE_URL'] ?? '',

    'auth_registration' => $_ENV['AUTH_REGISTRATION'] ?? 'direct',

    'github_url'  => $_ENV['ASTRAL_GITHUB_URL']  ?? 'https://github.com/astral-php/astral-forge',
    'website_url' => $_ENV['ASTRAL_WEBSITE_URL'] ?? 'https://astral-php.dev',

    'mail' => [
        'driver'      => $_ENV['MAIL_DRIVER']      ?? 'mail',
        'host'        => $_ENV['MAIL_HOST']         ?? 'localhost',
        'port'        => (int) ($_ENV['MAIL_PORT']  ?? 25),
        'username'    => $_ENV['MAIL_USERNAME']     ?? '',
        'password'    => $_ENV['MAIL_PASSWORD']     ?? '',
        'encryption'  => $_ENV['MAIL_ENCRYPTION']   ?? '',
        'from'        => $_ENV['MAIL_FROM']         ?? 'noreply@localhost',
        'from_name'   => $_ENV['MAIL_FROM_NAME']    ?? 'Astral Forge',
    ],
];
