<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Dao\UserDao;
use Controller\AbstractController;
use Core\Http\Response;
use Core\View;

/**
 * Accueil Forge — lien vers le générateur + préversion.
 */
final class HomeController extends AbstractController
{
    public function __construct(
        View $view,
        private UserDao $userDao,
        private string $version = '0.4.1',
        private string $githubUrl = 'https://github.com/astral-php/astral-forge',
        private string $websiteUrl = 'https://astral-php.dev',
    ) {
        parent::__construct($view);
    }

    public function index(): Response
    {
        return $this->render('home/index', [
            'title'      => 'Astral Forge',
            'version'    => $this->version,
            'hasUsers'   => $this->userDao->count() > 0,
            'githubUrl'  => $this->githubUrl,
            'websiteUrl' => $this->websiteUrl,
        ]);
    }
}
