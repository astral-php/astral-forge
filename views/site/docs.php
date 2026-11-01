<section class="content-page">
    <header class="content-hero">
        <p class="eyebrow"><span class="eyebrow-mark"></span> COMMENT UTILISER · V<?= htmlspecialchars((string) $version, ENT_QUOTES, 'UTF-8') ?></p>
        <h1>Documentation</h1>
        <p class="content-lead">Tutoriel d’usage — lancer Forge, UI, CLI, après génération. Mode <strong>local mono-utilisateur</strong>.</p>
    </header>

    <div class="content-prose">
        <h2>1. Lancer Forge</h2>
        <pre class="code-block"><code>cd astral-forge
cp .env.example .env   # si besoin
composer serve
# http://127.0.0.1:8090/  ou  http://astral-forge.local/</code></pre>
        <table class="content-table">
            <thead><tr><th>Mode</th><th>.env</th><th>Front</th></tr></thead>
            <tbody>
                <tr><td>Prod locale / Laragon</td><td><code>APP_ENV=production</code></td><td>assets dans <code>public/build/</code></td></tr>
                <tr><td>Dev HMR</td><td><code>APP_ENV=development</code></td><td><code>npm run dev</code> (port 5173) + PHP</td></tr>
            </tbody>
        </table>
        <p>Si « Chargement de l’UI Forge… » reste affiché : Vite n’est pas joignable → passe en <code>production</code> ou lance <code>npm run dev</code>.</p>

        <h2>2. Via l’UI (<a href="/forge">/forge</a>)</h2>
        <ol>
            <li>Ouvre <a href="/">/</a> → <strong>Nouveau projet</strong> (ou <a href="/forge">/forge</a>).</li>
            <li><strong>Nom</strong> en kebab-case (<code>mon-blog</code>).</li>
            <li><strong>Destination</strong> sous la racine autorisée (ex. <code>../mon-blog</code>).</li>
            <li><strong>Recette :</strong> blank · blog-lite · api-json (détail <a href="/templates">Templates</a>).</li>
            <li><strong>Voir le plan</strong> (aucune écriture).</li>
            <li>Cocher la confirmation → <strong>Générer</strong>.</li>
            <li>Lire le rapport.</li>
        </ol>
        <p><code>/forge</code> est <strong>public</strong> : pas besoin de compte.</p>

        <h2>3. Via CLI</h2>
        <pre class="code-block"><code>php bin/forge plan resources/blueprints/blank.example.json --dest=../mon-app
php bin/forge generate resources/blueprints/blank.example.json --dest=../mon-app --dry-run
php bin/forge generate resources/blueprints/blank.example.json --dest=../mon-app --confirm</code></pre>
        <p>Exemples : <code>blog-lite.example.json</code> · <code>api-json.example.json</code>. Équivalent (core ≥ 2.1.2) : <code>php bin/console forge:plan|forge:generate</code>.</p>

        <h2>4. Après génération</h2>
        <pre class="code-block"><code>cd ../mon-app
cp .env.example .env
php bin/console migrate
composer serve</code></pre>
        <table class="content-table">
            <thead><tr><th>Recette</th><th>Piste</th></tr></thead>
            <tbody>
                <tr><td>blank</td><td>auth starter + admin users</td></tr>
                <tr><td>blog-lite</td><td><code>/articles</code> (CRUD)</td></tr>
                <tr><td>api-json</td><td><code>GET /api/v1/health</code></td></tr>
            </tbody>
        </table>

        <h2>5. Auth (shell starter)</h2>
        <p>Hérité d’astral-starter ; <strong>non requis</strong> pour générer. Inscription / connexion masquées dans la nav (mode mono-user). Routes <code>/login</code>, <code>/register</code> encore présentes en accès direct.</p>

        <h2>6. Limites</h2>
        <ul>
            <li>Greenfield uniquement (destination absente ou vide)</li>
            <li>Pas de FTP / deploy (outil interne <code>astral-deploy</code>, hors Forge)</li>
            <li>Préversion <code>@dev</code> jusqu’à 1.0.0</li>
            <li>Chemins hors <code>FORGE_ALLOWED_ROOT</code> refusés</li>
        </ul>
    </div>

    <p class="content-note"><a href="/discover">Découvrir Forge</a> · <a href="/templates">Templates</a> · <a href="/news">Actualités</a></p>
</section>
