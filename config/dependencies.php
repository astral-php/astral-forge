<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use AstralVite\ViteServiceProvider;
use Core\Providers\DatabaseServiceProvider;
use Core\Providers\FrameworkServiceProvider;

return [
    FrameworkServiceProvider::class,
    DatabaseServiceProvider::class,
    ViteServiceProvider::class,
    AppServiceProvider::class,
];
