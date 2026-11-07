<?php
/** @var \App\Models\ForgeProject $project */
/** @var bool $exists */
/** @var list<array{entity: string, table: string, route: string}> $modules */
/** @var array{error?: string, entity?: string}|null $moduleResult */
/** @var array{ok: bool, badge: string, label: string, pending_count: int, applied_count: int, orphan_count: int, rows: list<array{migration: string, batch: int|null, status: string, executed_at: string|null}>, error: ?string}|null $migrationStatus */
/** @var string|null $cliJobId */
/** @var string $cliJobLabel */
$cliJobId = $cliJobId ?? null;
$cliJobLabel = $cliJobLabel ?? 'Migration';
$moduleResult = $moduleResult ?? null;
$modules = $modules ?? [];
$migrationStatus = $migrationStatus ?? null;

$badgeClass = static function (string $badge): string {
    return match ($badge) {
        'up_to_date' => 'tag-ok',
        'pending' => 'tag-warn',
        'orphan', 'error' => 'tag-err',
        default => 'tag-neutral',
    };
};

$statusLabel = static function (string $status): string {
    return match ($status) {
        'applied' => 'Appliquée',
        'pending' => 'En attente',
        'orphan' => 'Orpheline',
        default => $status,
    };
};

$migrationShort = static function (string $file): string {
    $base = basename(str_replace('\\', '/', $file));
    $short = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $base);

    return is_string($short) && $short !== '' ? $short : $base;
};
?>
<section class="content-page forge-project-page">
    <div class="forge-project-top">
        <header class="forge-project-identity">
            <p class="eyebrow"><span class="eyebrow-mark"></span> ATELIER</p>
            <h1><?= htmlspecialchars($project->name, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="content-lead">
                Recette <strong><?= htmlspecialchars($project->recipe, ENT_QUOTES, 'UTF-8') ?></strong>
                · <?= $exists ? 'dossier présent' : 'dossier introuvable' ?>
            </p>
            <p class="content-meta forge-project-path">
                <code><?= htmlspecialchars($project->path, ENT_QUOTES, 'UTF-8') ?></code>
            </p>
            <p class="forge-project-back"><a href="/forge">← Mes projets</a></p>
        </header>

        <aside
            class="content-card forge-cli-panel<?= $cliJobId ? ' is-active' : '' ?>"
            id="cli-job-panel"
            data-project-url="/forge/projects/<?= (int) $project->id ?>"
            <?php if ($cliJobId): ?>
                data-job="<?= htmlspecialchars($cliJobId, ENT_QUOTES, 'UTF-8') ?>"
                data-label="<?= htmlspecialchars($cliJobLabel, ENT_QUOTES, 'UTF-8') ?>"
            <?php endif ?>
        >
            <div class="content-card-head">
                <h2>Activité</h2>
                <span id="cli-job-badge" class="tag <?= $cliJobId ? 'tag-purple' : 'tag-neutral' ?>">
                    <?= $cliJobId ? 'en cours' : 'repos' ?>
                </span>
            </div>
            <?php if ($cliJobId): ?>
                <p id="cli-job-status"><?= htmlspecialchars($cliJobLabel, ENT_QUOTES, 'UTF-8') ?> lancé en arrière-plan…</p>
                <pre id="cli-job-output" class="forge-cli-output" hidden></pre>
            <?php else: ?>
                <p id="cli-job-status" class="content-meta">Aucune commande en cours. Migrer, rollback ou création de module apparaîtront ici.</p>
                <pre id="cli-job-output" class="forge-cli-output" hidden></pre>
            <?php endif ?>
        </aside>
    </div>

    <?php if ($moduleResult && isset($moduleResult['error'])): ?>
        <div class="flash-err" role="alert"><?= htmlspecialchars($moduleResult['error'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif ?>

    <?php if ($exists): ?>
        <div class="forge-project-grid">
            <article class="content-card forge-project-card">
                <div class="content-card-head">
                    <h2>Modules</h2>
                    <span class="tag tag-purple"><?= count($modules) ?></span>
                </div>
                <?php if ($modules === []): ?>
                    <p>Aucun module métier (hors auth <code>User</code>).</p>
                    <p class="content-meta">Ex. <strong>Category</strong> — migrate auto.</p>
                <?php else: ?>
                    <ul class="forge-module-list">
                        <?php foreach ($modules as $mod): ?>
                            <li class="forge-module-row">
                                <div>
                                    <strong><?= htmlspecialchars($mod['entity'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <p class="content-meta">
                                        <code><?= htmlspecialchars($mod['table'], ENT_QUOTES, 'UTF-8') ?></code>
                                        · <code><?= htmlspecialchars($mod['route'], ENT_QUOTES, 'UTF-8') ?></code>
                                    </p>
                                </div>
                                <span class="tag tag-neutral">CRUD</span>
                            </li>
                        <?php endforeach ?>
                    </ul>
                <?php endif ?>
            </article>

            <article class="content-card forge-project-card">
                <div class="content-card-head">
                    <h2>Base de données</h2>
                    <?php if ($migrationStatus): ?>
                        <span class="tag <?= htmlspecialchars($badgeClass($migrationStatus['badge']), ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($migrationStatus['label'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    <?php endif ?>
                </div>
                <p class="content-meta">CLI : <code>migrate</code> · <code>status</code> · <code>rollback</code></p>

                <?php if ($migrationStatus && $migrationStatus['error']): ?>
                    <div class="flash-err"><?= htmlspecialchars($migrationStatus['error'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif ?>

                <?php if ($migrationStatus && $migrationStatus['rows'] !== []): ?>
                    <ul class="forge-migration-list">
                        <?php foreach ($migrationStatus['rows'] as $row): ?>
                            <li class="forge-migration-row tone-<?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?>">
                                <code
                                    class="forge-migration-name"
                                    title="<?= htmlspecialchars($row['migration'], ENT_QUOTES, 'UTF-8') ?>"
                                ><?= htmlspecialchars($migrationShort($row['migration']), ENT_QUOTES, 'UTF-8') ?></code>
                                <span class="tag <?= $row['status'] === 'applied' ? 'tag-ok' : ($row['status'] === 'pending' ? 'tag-warn' : 'tag-err') ?>">
                                    <?= htmlspecialchars($statusLabel($row['status']), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </li>
                        <?php endforeach ?>
                    </ul>
                    <p class="content-meta">
                        Appliquées : <?= (int) $migrationStatus['applied_count'] ?>
                        · En attente : <?= (int) $migrationStatus['pending_count'] ?>
                        <?php if ((int) $migrationStatus['orphan_count'] > 0): ?>
                            · Orphelines : <?= (int) $migrationStatus['orphan_count'] ?>
                        <?php endif ?>
                    </p>
                <?php elseif ($migrationStatus && $migrationStatus['ok']): ?>
                    <p class="content-meta">Aucune migration dans <code>database/migrations</code>.</p>
                <?php endif ?>

                <div class="forge-card-actions">
                    <form method="POST" action="/forge/projects/<?= (int) $project->id ?>/migrate">
                        <?= $csrf->field() ?>
                        <button type="submit" class="btn btn-primary forge-btn-compact">Migrer</button>
                    </form>
                    <a class="btn btn-ghost forge-btn-compact" href="/forge/projects/<?= (int) $project->id ?>">Rafraîchir</a>
                </div>

                <details class="forge-card-details">
                    <summary>Rollback (dernier batch)</summary>
                    <p class="content-meta">Action destructive — confirmation requise.</p>
                    <form method="POST" action="/forge/projects/<?= (int) $project->id ?>/migrate-rollback" class="form-stack">
                        <?= $csrf->field() ?>
                        <label class="forge-confirm-label">
                            <input type="checkbox" name="confirm" value="1" required />
                            <span>Je confirme le rollback du dernier batch.</span>
                        </label>
                        <button type="submit" class="btn forge-btn-danger forge-btn-compact">Rollback</button>
                    </form>
                </details>
            </article>

            <article class="content-card forge-project-card">
                <div class="content-card-head"><h2>Créer un module</h2></div>
                <p class="content-meta">PascalCase + migrate auto. Réservés : <code>User</code>…</p>
                <form method="POST" action="/forge/projects/<?= (int) $project->id ?>/modules" class="form-stack">
                    <?= $csrf->field() ?>
                    <div class="field">
                        <label for="entity">Entité</label>
                        <input class="input" id="entity" name="entity" value="<?= htmlspecialchars((string) ($moduleResult['entity'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Category" required pattern="[A-Z][A-Za-z0-9]*" />
                    </div>
                    <label class="forge-confirm-label">
                        <input type="checkbox" name="confirm" value="1" required />
                        <span>J’écris les fichiers <strong>et</strong> je migre.</span>
                    </label>
                    <button type="submit" class="btn btn-primary forge-btn-compact">Créer le module</button>
                </form>
            </article>
        </div>

        <details class="content-card forge-danger-zone">
            <summary>
                <span>Zone dangereuse</span>
                <span class="tag tag-err">attention</span>
            </summary>
            <p class="content-meta">Deux actions distinctes — lisez avant de confirmer.</p>

            <div class="forge-danger-grid">
                <div>
                    <h3>Retirer du registre</h3>
                    <p class="content-meta">Enlève l’entrée Forge. Le dossier n’est <strong>pas</strong> touché.</p>
                    <form method="POST" action="/forge/projects/<?= (int) $project->id ?>/delete">
                        <?= $csrf->field() ?>
                        <button
                            type="submit"
                            class="btn forge-btn-compact forge-btn-ghost-border"
                            title="Retire l’entrée du registre Forge. Le dossier reste intact."
                        >Retirer du registre</button>
                    </form>
                </div>
                <div>
                    <h3>Supprimer le projet</h3>
                    <p class="content-meta">Efface le dossier <strong>et</strong> le registre. Irréversible.</p>
                    <form method="POST" action="/forge/projects/<?= (int) $project->id ?>/purge" class="form-stack">
                        <?= $csrf->field() ?>
                        <label class="forge-confirm-label">
                            <input type="checkbox" name="confirm" value="1" required />
                            <span>Je confirme la suppression de <code><?= htmlspecialchars(basename(str_replace('\\', '/', $project->path)), ENT_QUOTES, 'UTF-8') ?></code>.</span>
                        </label>
                        <button
                            type="submit"
                            class="btn forge-btn-danger forge-btn-compact"
                            title="Attention : action irréversible — le dossier sera effacé du disque."
                        >Supprimer le projet</button>
                    </form>
                </div>
            </div>
        </details>
    <?php else: ?>
        <article class="content-card">
            <p>Le dossier n’existe plus sur le disque. Vous pouvez retirer l’entrée du registre.</p>
            <form method="POST" action="/forge/projects/<?= (int) $project->id ?>/delete">
                <?= $csrf->field() ?>
                <button
                    type="submit"
                    class="btn btn-primary forge-btn-compact"
                    title="Retire l’entrée du registre. Aucun fichier à supprimer (dossier déjà absent)."
                >Retirer du registre</button>
            </form>
        </article>
    <?php endif ?>
</section>

<?php if ($cliJobId): ?>
<script>
(function () {
  const panel = document.getElementById('cli-job-panel')
  if (!panel) return
  const jobId = panel.getAttribute('data-job')
  if (!jobId) return
  const label = panel.getAttribute('data-label') || 'Migration'
  const projectUrl = panel.getAttribute('data-project-url') || window.location.pathname
  const statusEl = document.getElementById('cli-job-status')
  const outEl = document.getElementById('cli-job-output')
  const badgeEl = document.getElementById('cli-job-badge')
  let finished = false
  const setBadge = (text, tone) => {
    if (!badgeEl) return
    badgeEl.textContent = text
    badgeEl.className = 'tag ' + (tone === 'ok' ? 'tag-ok' : tone === 'err' ? 'tag-err' : 'tag-purple')
  }
  const goToProject = () => {
    // GET propre — évite de rejouer le POST Migrer (boucle reload).
    window.location.replace(projectUrl)
  }
  let n = 0
  const tick = async () => {
    if (finished) return
    n += 1
    if (badgeEl && badgeEl.textContent === 'en cours') {
      statusEl.textContent = label + ' en cours… (' + (n * 2) + 's)'
    }
    try {
      const res = await fetch('/forge/api/cli-jobs/' + encodeURIComponent(jobId), { headers: { Accept: 'application/json' } })
      const data = await res.json()
      const job = data.job
      if (!job) {
        if (n < 90) setTimeout(tick, 2000)
        return
      }
      if (job.status === 'done') {
        finished = true
        setBadge('OK', 'ok')
        statusEl.textContent = label + ' terminé.'
        if (job.output) { outEl.hidden = false; outEl.textContent = job.output }
        setTimeout(goToProject, 1200)
        return
      }
      if (job.status === 'failed') {
        finished = true
        setBadge('échec', 'err')
        statusEl.textContent = label + ' en échec (projet / fichiers toujours OK).'
        outEl.hidden = false
        outEl.textContent = job.error || job.output || 'Erreur'
        return
      }
    } catch (e) {}
    if (n < 90) setTimeout(tick, 2000)
    else {
      finished = true
      setBadge('délai', 'err')
      statusEl.textContent = 'Délai dépassé — vérifiez le projet en CLI.'
    }
  }
  setTimeout(tick, 1500)
})()
</script>
<?php endif ?>
