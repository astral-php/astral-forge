<section class="hero-panel">
    <div class="hero-copy">
        <p class="eyebrow"><span class="eyebrow-mark"></span> GÉNÉRATEUR ASTRAL · VUE + VITE · PRÉVERSION V<?= htmlspecialchars((string) $version, ENT_QUOTES, 'UTF-8') ?></p>
        <h1>Astral <span>Forge</span></h1>
        <h2>Créez rapidement des applications avec<br class="desktop-break"> le framework Astral PHP</h2>
        <p class="hero-description">Blueprint déclaratif, plan sans I/O, génération greenfield sur <strong>astral-starter ^2.1</strong>. Outil local — pas de compte requis.</p>
        <div class="hero-actions">
            <a class="btn btn-primary" href="/forge/new"><span class="btn-plus">＋</span> Nouveau projet</a>
            <a class="btn btn-ghost" href="/discover"><span>▶</span> Découvrir Forge</a>
        </div>
        <div class="hero-meta"><span><i></i> Génération locale</span><span>PHP 8.3+</span><span>Blueprint JSON</span></div>
    </div>
    <div class="hero-art" aria-label="Aperçu du terminal Astral Forge">
        <div class="code-window">
            <div class="window-bar"><span></span><span></span><span></span><small>TERMINAL · ASTRAL FORGE</small></div>
            <div class="terminal-content">
                <div class="terminal-brand">▲ <span>astral forge</span></div>
                <p><b>$</b> php bin/forge plan <em>blank.example.json</em> --dest=../mon-app</p>
                <p class="terminal-ok">✓ <span>Plan déterministe (aucune écriture)</span></p>
                <p><b>$</b> php bin/forge generate … --confirm</p>
                <p class="terminal-ok">✓ <span>Génération greenfield</span></p>
                <p class="terminal-ok">✓ <span>Projet prêt</span></p>
                <div class="terminal-cursor"></div>
            </div>
        </div>
        <div class="art-float art-code">&lt;/&gt;</div>
        <div class="art-float art-check"><strong>☷</strong><span>Plan puis generate<br>en local</span></div>
    </div>
</section>

<section class="feature-strip" aria-label="Atouts d'Astral Forge">
    <article class="feature-item"><span class="feature-icon">↗</span><div><h3>Démarrage rapide</h3><p>UI `/forge` ou CLI — plan dry-run puis génération.</p></div></article>
    <article class="feature-item"><span class="feature-icon">▱</span><div><h3>Templates prêts</h3><p>blank, blog-lite, api-json — recettes documentées.</p></div></article>
    <article class="feature-item"><span class="feature-icon">♧</span><div><h3>Écosystème Astral</h3><p>Composants Packagist à composer selon le besoin.</p></div></article>
    <article class="feature-item"><span class="feature-icon">◇</span><div><h3>Fiable et moderne</h3><p>PHP 8.3+, core ^2.1, Vue 3 + astral-vite.</p></div></article>
</section>

<section class="dashboard-grid">
    <article class="dashboard-card quick-card">
        <div class="card-heading"><span class="heading-icon">ϟ</span><h2>Accès rapide</h2></div>
        <div class="quick-grid">
            <a class="quick-action" href="/forge/new"><span>＋</span><strong>Nouveau projet</strong></a>
            <a class="quick-action" href="/templates"><span>▦</span><strong>Templates</strong></a>
            <a class="quick-action" href="/docs"><span>▤</span><strong>Documentation</strong></a>
            <a class="quick-action" href="/news"><span>▥</span><strong>Actualités</strong></a>
        </div>
    </article>
    <article class="dashboard-card recipe-card" id="recettes">
        <div class="card-heading"><span class="heading-icon">▱</span><h2>Recettes disponibles</h2></div>
        <a class="recipe-row" href="/templates"><span class="recipe-file">▱</span><span class="recipe-text"><strong>blank</strong><small>Application Astral vierge · starter ^2.1</small></span><span class="tag tag-neutral">Starter</span><span class="row-arrow">›</span></a>
        <a class="recipe-row" href="/templates"><span class="recipe-file">▤</span><span class="recipe-text"><strong>blog-lite</strong><small>Starter + module Article CRUD</small></span><span class="tag tag-purple">Blog</span><span class="row-arrow">›</span></a>
        <a class="recipe-row" href="/templates"><span class="recipe-file">⌘</span><span class="recipe-text"><strong>api-json</strong><small>API REST JSON + CORS</small></span><span class="tag tag-blue">API</span><span class="row-arrow">›</span></a>
        <a class="card-more" href="/templates">Voir les templates <span>→</span></a>
    </article>
    <article class="dashboard-card news-card">
        <div class="card-heading"><span class="heading-icon">▥</span><h2>Aller plus loin</h2></div>
        <a class="news-row" href="/discover"><span class="news-icon">▲</span><span><strong>Découvrir Forge</strong><small>Rôle, parcours, mode mono-utilisateur.</small></span></a>
        <a class="news-row" href="/extensions"><span class="news-icon">♧</span><span><strong>Extensions</strong><small>Composants Astral utiles (Packagist).</small></span></a>
        <a class="news-row" href="/docs"><span class="news-icon">▤</span><span><strong>Documentation</strong><small>Comment lancer, UI, CLI, après génération.</small></span></a>
        <a class="card-more" href="/news">Actualités <span>→</span></a>
    </article>
</section>
