<section style="padding: 2.5rem 0 1rem">
    <p style="font-size:0.8rem;letter-spacing:0.08em;text-transform:uppercase;color:var(--muted);margin:0 0 0.75rem">
        Générateur Astral · Vue + Vite · préversion v<?= htmlspecialchars((string) $version, ENT_QUOTES, 'UTF-8') ?>
    </p>
    <h1 style="font-family:Fraunces,Georgia,serif;font-size:clamp(2rem,5vw,2.75rem);line-height:1.15;margin:0 0 1rem">
        Astral Forge
    </h1>
    <p style="font-size:1.1rem;color:var(--muted);max-width:36rem;line-height:1.55;margin:0 0 1.75rem">
        Blueprint déclaratif, plan sans I/O, génération greenfield sur
        <strong style="color:var(--ink)">astral-starter ^2.1</strong>.
        Outil local — pas de compte requis.
    </p>

    <div style="display:flex;flex-wrap:wrap;gap:0.75rem;margin-bottom:2.5rem">
        <a class="btn btn-primary" href="/forge">Créer un projet</a>
        <a class="btn btn-ghost" href="<?= htmlspecialchars((string) $githubUrl, ENT_QUOTES, 'UTF-8') ?>"
           target="_blank" rel="noopener">GitHub</a>
    </div>

    <div style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
        <div style="padding:1.1rem 1.2rem;border:1px solid var(--line);border-radius:16px;background:var(--panel)">
            <strong style="display:block;margin-bottom:0.35rem">CLI</strong>
            <span style="font-size:0.9rem;color:var(--muted);line-height:1.45">
                <code style="font-size:0.8rem">php bin/forge plan …</code><br>
                <code style="font-size:0.8rem">php bin/forge generate … --confirm</code>
            </span>
        </div>
        <div style="padding:1.1rem 1.2rem;border:1px solid var(--line);border-radius:16px;background:var(--panel)">
            <strong style="display:block;margin-bottom:0.35rem">Recettes</strong>
            <span style="font-size:0.9rem;color:var(--muted);line-height:1.45">
                blank · blog-lite · api-json
            </span>
        </div>
    </div>
</section>
