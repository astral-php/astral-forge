<?php
/** @var list<\App\Models\ForgeProject> $projects */
?>
<section class="content-page">
    <header class="content-hero">
        <p class="eyebrow"><span class="eyebrow-mark"></span> ATELIER · 0.5</p>
        <h1>Mes projets</h1>
        <p class="content-lead">Projets générés par Forge — migrer, créer un module, ou ouvrir le dossier.</p>
        <?php if ($projects !== []): ?>
            <p style="margin-top:18px">
                <a class="btn btn-primary" href="/forge/new">Nouveau projet</a>
            </p>
        <?php endif ?>
    </header>

    <?php if ($projects === []): ?>
        <article class="content-card">
            <p>Aucun projet enregistré pour l’instant.</p>
            <p class="content-meta">Après une génération réussie, le projet apparaît ici automatiquement.</p>
            <p><a class="btn btn-primary" href="/forge/new">Nouveau projet</a></p>
        </article>
    <?php else: ?>
        <div class="content-stack">
            <?php foreach ($projects as $project): ?>
                <?php $exists = $project->directoryExists(); ?>
                <article class="content-card">
                    <div class="content-card-head">
                        <h2><?= htmlspecialchars($project->name, ENT_QUOTES, 'UTF-8') ?></h2>
                        <span class="tag <?= $exists ? 'tag-purple' : 'tag-neutral' ?>">
                            <?= $exists ? htmlspecialchars($project->recipe, ENT_QUOTES, 'UTF-8') : 'dossier manquant' ?>
                        </span>
                    </div>
                    <p><code style="font-size:12px;word-break:break-all"><?= htmlspecialchars($project->path, ENT_QUOTES, 'UTF-8') ?></code></p>
                    <p class="content-meta">Mis à jour <?= htmlspecialchars($project->updated_at, ENT_QUOTES, 'UTF-8') ?></p>
                    <p style="display:flex;flex-wrap:wrap;gap:10px;margin-top:12px;margin-bottom:0">
                        <a class="btn btn-primary" href="/forge/projects/<?= (int) $project->id ?>" style="padding:8px 14px;font-size:13px">Ouvrir</a>
                        <?php if (!$exists): ?>
                            <form method="POST" action="/forge/projects/<?= (int) $project->id ?>/delete" style="display:inline">
                                <?= $csrf->field() ?>
                                <button
                                    type="submit"
                                    class="btn"
                                    style="padding:8px 14px;font-size:13px;background:#fff;border:1px solid var(--line)"
                                    title="Retire l’entrée du registre. Aucun fichier à supprimer (dossier déjà absent)."
                                >Retirer du registre</button>
                            </form>
                        <?php endif ?>
                    </p>
                </article>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</section>
