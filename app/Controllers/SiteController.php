<?php

declare(strict_types=1);

namespace App\Controllers;

use Controller\AbstractController;
use Core\Http\Response;
use Core\View;

/**
 * Pages contenu 0.4 — templates, docs, discover, extensions, news.
 */
final class SiteController extends AbstractController
{
    public function __construct(
        View $view,
        private string $version = '0.4.0',
        private string $githubUrl = 'https://github.com/astral-php/astral-forge',
        private string $websiteUrl = 'https://astral-php.dev',
    ) {
        parent::__construct($view);
    }

    public function templates(): Response
    {
        return $this->render('site/templates', [
            'title'   => 'Templates — Astral Forge',
            'version' => $this->version,
        ]);
    }

    public function docs(): Response
    {
        return $this->render('site/docs', [
            'title'   => 'Documentation — Astral Forge',
            'version' => $this->version,
        ]);
    }

    public function discover(): Response
    {
        return $this->render('site/discover', [
            'title'      => 'Découvrir Forge — Astral Forge',
            'version'    => $this->version,
            'githubUrl'  => $this->githubUrl,
            'websiteUrl' => $this->websiteUrl,
        ]);
    }

    public function extensions(): Response
    {
        return $this->render('site/extensions', [
            'title'   => 'Extensions — Astral Forge',
            'version' => $this->version,
        ]);
    }

    public function news(): Response
    {
        return $this->render('site/news', [
            'title'   => 'Actualités — Astral Forge',
            'version' => $this->version,
        ]);
    }
}
