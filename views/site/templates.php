<section class="content-page">
    <header class="content-hero">
        <p class="eyebrow"><span class="eyebrow-mark"></span> RECETTES MVP</p>
        <h1>Templates</h1>
        <p class="content-lead">Trois recettes livrées. Forge génère surtout <strong>blank + modules</strong> ; pour un blog/shop/forum complets, préférer les apps officielles <code>create-project</code>.</p>
    </header>

    <div class="content-stack">
        <article class="content-card">
            <div class="content-card-head">
                <h2>blank</h2>
                <span class="tag tag-neutral">Starter</span>
            </div>
            <p>Application Astral vierge = <strong>astral-starter ^2.1</strong> (auth + admin users + layout).</p>
            <p class="content-meta">Après génération : auth starter, admin users.</p>
            <a class="btn btn-primary" href="/forge/new">Générer blank</a>
        </article>

        <article class="content-card">
            <div class="content-card-head">
                <h2>blog-lite</h2>
                <span class="tag tag-purple">Blog</span>
            </div>
            <p>Starter + module <strong>Article</strong> (CRUD + liste publique). Package recommandé : <code>astral-form</code>.</p>
            <p class="content-meta">Piste : <code>/articles</code> dans le projet généré.</p>
            <a class="btn btn-primary" href="/forge/new">Générer blog-lite</a>
        </article>

        <article class="content-card">
            <div class="content-card-head">
                <h2>api-json</h2>
                <span class="tag tag-blue">API</span>
            </div>
            <p>Starter + routes API v1 + Cors (style hub).</p>
            <p class="content-meta">Piste : <code>GET /api/v1/health</code>.</p>
            <a class="btn btn-primary" href="/forge/new">Générer api-json</a>
        </article>
    </div>

    <p class="content-note">Différé : <code>forum-seed</code> / <code>shop-seed</code> (pointer vers create-project des apps officielles). Voir aussi <a href="/docs">Documentation</a>.</p>
</section>
