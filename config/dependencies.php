<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Core\Providers\DatabaseServiceProvider;
use Core\Providers\FrameworkServiceProvider;

return [
    FrameworkServiceProvider::class,
    DatabaseServiceProvider::class,
    AppServiceProvider::class,
];
