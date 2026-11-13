<section class="content-page">
    <header class="content-hero">
        <p class="eyebrow"><span class="eyebrow-mark"></span> À PROPOS</p>
        <h1>Découvrir Forge</h1>
        <p class="content-lead">Astral Forge génère des projets <strong>Astral MVC 2.1</strong> à partir d’un blueprint JSON : plan déterministe sans I/O, puis génération greenfield.</p>
    </header>

    <div class="content-prose">
        <h2>À quoi ça sert</h2>
        <ul>
            <li>Démarrer une app sur <strong>astral-starter ^2.1</strong> (PHP 8.3+, core ^2.1)</li>
            <li>Composer des recettes MVP : blank, blog-lite, api-json</li>
            <li>UI Vue 3 + astral-vite (<code>/forge</code>) et CLI dédiée (<code>php bin/forge</code>)</li>
        </ul>

        <h2>Parcours recommandé</h2>
        <ol>
            <li>Lire la <a href="/docs">documentation</a> (lancer + UI + CLI)</li>
            <li>Choisir un <a href="/templates">template</a></li>
            <li>Ouvrir <a href="/forge/new">le générateur</a> → plan → confirmer → générer</li>
        </ol>

        <h2>Mode actuel</h2>
        <ul>
            <li><strong>Local mono-utilisateur</strong> — pas de compte requis pour générer</li>
            <li>Auth starter masquée dans la nav (réactivable plus tard si multi-dev)</li>
            <li>Version stable <strong>1.0.0</strong></li>
        </ul>

        <h2>Ce que Forge ne fait pas</h2>
        <ul>
            <li>Deploy / FTP (réservé à <code>astral-deploy</code>, hors Forge)</li>
            <li>Écraser un projet existant (greenfield uniquement)</li>
            <li>Remplacer les apps officielles complètes (forum, shop…) — préférer <code>create-project</code></li>
        </ul>
    </div>

    <div class="hero-actions" style="margin-top:1.5rem">
        <a class="btn btn-primary" href="/forge/new">Nouveau projet</a>
        <a class="btn btn-ghost" href="<?= htmlspecialchars((string) $githubUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">GitHub</a>
    </div>
</section>
