<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#a90f19">
    <title><?= htmlspecialchars($title ?? 'Astral Forge', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <?php if (isset($vite)): ?>
        <?= $vite->tags(['resources/js/app.js', 'resources/css/app.css']) ?>
    <?php else: ?>
        <script src="https://cdn.tailwindcss.com"></script>
    <?php endif ?>
</head>
<?php
$uri = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$navActive = static function (string $path) use ($uri): string {
    if ($path === '/') {
        return $uri === '/' ? 'is-active' : '';
    }

    return str_starts_with($uri, $path) ? 'is-active' : '';
};
?>
<body class="forge-body">
    <div class="site-shell">
        <header class="topbar">
            <a class="brand-lockup" href="/" aria-label="Astral Forge, accueil">
                <span class="brand-symbol" aria-hidden="true">A</span>
                <span class="brand-copy"><strong>Astral <b>Forge</b></strong><small>Générateur d'applications Astral</small></span>
            </a>
            <nav class="main-nav" aria-label="Navigation principale">
                <a class="nav-link <?= $navActive('/') ?>" href="/">⌂ <span>Accueil</span></a>
                <a class="nav-link <?= $navActive('/forge') ?>" href="/forge">▱ <span>Projets</span></a>
                <a class="nav-link <?= $navActive('/templates') ?>" href="/templates">▦ <span>Templates</span></a>
                <a class="nav-link <?= $navActive('/docs') ?>" href="/docs">▤ <span>Documentation</span></a>
                <a class="nav-link <?= $navActive('/extensions') ?>" href="/extensions">♧ <span>Extensions</span></a>
            </nav>
            <div class="topbar-actions">
                <span class="status-dot" title="Application locale"></span>
                <?php if ($auth->check()): ?>
                    <span class="user-chip"><span class="avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr((string) $auth->name(), 0, 1)), ENT_QUOTES, 'UTF-8') ?></span><span><?= htmlspecialchars($auth->name(), ENT_QUOTES, 'UTF-8') ?></span></span>
                    <?php if ($auth->is(\Core\Auth\Role::Admin)): ?><a class="nav-utility" href="/admin/users">Admin</a><?php endif ?>
                    <form method="POST" action="/logout" class="logout-form"><?= $csrf->field() ?><button type="submit" class="nav-utility">Déconnexion</button></form>
                <?php else: ?>
                    <a class="btn btn-primary" href="/forge" style="padding:10px 16px;font-size:13px">Nouveau projet</a>
                <?php endif ?>
            </div>
        </header>

        <main class="page-content">
            <?php if ($session->hasFlash('success')): ?>
                <div class="flash-ok" role="status"><?= htmlspecialchars((string) $session->getFlash('success'), ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif ?>
            <?php if ($session->hasFlash('error')): ?>
                <div class="flash-err" role="alert"><?= htmlspecialchars((string) $session->getFlash('error'), ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif ?>
            <?= $content ?? '' ?>
        </main>
        <footer class="site-footer">
            <span>Astral Forge <b>·</b> nova-red · préversion</span>
            <span>
                <a href="/discover">Découvrir</a>
                · <a href="/news">Actualités</a>
                · <a href="/docs">Docs</a>
            </span>
        </footer>
    </div>
</body>
</html>
