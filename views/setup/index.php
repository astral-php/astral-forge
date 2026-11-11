<?php
/** @var array{needs_migrate: bool, pending_count: int, applied_count: int, rows: list<array{migration: string, batch: int|null, status: string, executed_at: string|null}>, error: ?string} $summary */
/** @var string $cliHint */
?>
<section class="content-page">
    <header class="content-hero">
        <p class="eyebrow"><span class="eyebrow-mark"></span> INSTALL · 0.6</p>
        <h1>Installation Forge</h1>
        <p class="content-lead">
            La base locale n’est pas à jour. Appliquez les migrations avant d’utiliser l’atelier
            (Mes projets, génération).
        </p>
    </header>

    <article class="content-card">
        <?php if ($summary['error'] !== null): ?>
            <p class="flash-err" role="alert"><?= htmlspecialchars($summary['error'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php else: ?>
            <p>
                <strong><?= (int) $summary['pending_count'] ?></strong> migration(s) en attente
                · <strong><?= (int) $summary['applied_count'] ?></strong> déjà appliquée(s)
            </p>
        <?php endif ?>

        <?php if ($summary['rows'] !== []): ?>
            <ul class="content-meta" style="margin:16px 0;padding-left:18px">
                <?php foreach ($summary['rows'] as $row): ?>
                    <li>
                        <code><?= htmlspecialchars($row['migration'], ENT_QUOTES, 'UTF-8') ?></code>
                        —
                        <?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>

        <form method="POST" action="/setup/migrate" class="form-stack" style="margin-top:18px">
            <?= $csrf->field() ?>
            <button type="submit" class="btn btn-primary">Installer / Migrer</button>
        </form>

        <p class="content-meta" style="margin-top:20px">
            Équivalent CLI : <code><?= htmlspecialchars($cliHint, ENT_QUOTES, 'UTF-8') ?></code>
        </p>
    </article>
</section>
