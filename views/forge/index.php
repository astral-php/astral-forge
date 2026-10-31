<?php
/** @var array{project_name: string, destination: string, recipe: string} $form */
/** @var array<string, mixed>|null $plan */
/** @var array<string, mixed>|null $report */
?>
<section style="padding: 1.5rem 0 1rem">
    <p style="font-size:0.8rem;letter-spacing:0.08em;text-transform:uppercase;color:var(--muted);margin:0 0 0.75rem">
        Préversion 0.2 · blank · blog-lite · api-json
    </p>
    <h1 style="font-family:Fraunces,Georgia,serif;font-size:clamp(1.75rem,4vw,2.25rem);margin:0 0 0.75rem">
        Nouveau projet Astral
    </h1>
    <p style="color:var(--muted);max-width:36rem;line-height:1.55;margin:0 0 1.5rem">
        Blueprint → plan → génération greenfield (<code>astral-starter:^2.1</code> + modules).
    </p>

    <form method="POST" action="/forge/plan" class="auth-card" style="max-width:36rem;margin-bottom:1.5rem">
        <?= $csrf->field() ?>
        <div class="field">
            <label for="project_name">Nom du projet (kebab-case)</label>
            <input class="input" id="project_name" name="project_name" required
                   value="<?= htmlspecialchars($form['project_name'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="field">
            <label for="destination">Destination (chemin sous racine autorisée)</label>
            <input class="input" id="destination" name="destination" required
                   placeholder="ex. ../demo-forge-app"
                   value="<?= htmlspecialchars($form['destination'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="field">
            <label for="recipe">Recette</label>
            <select class="input" id="recipe" name="recipe">
                <option value="blank" <?= $form['recipe'] === 'blank' ? 'selected' : '' ?>>blank (starter)</option>
                <option value="blog-lite" <?= $form['recipe'] === 'blog-lite' ? 'selected' : '' ?>>blog-lite (+ Article CRUD)</option>
                <option value="api-json" <?= $form['recipe'] === 'api-json' ? 'selected' : '' ?>>api-json (/api/v1 + Cors)</option>
            </select>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:0.75rem;margin-top:1.25rem">
            <button type="submit" class="btn btn-primary" style="cursor:pointer;font:inherit">Voir le plan</button>
        </div>
    </form>

    <?php if (is_array($plan)): ?>
        <div class="auth-card" style="max-width:36rem;margin-bottom:1.5rem">
            <h2 style="font-family:Fraunces,Georgia,serif;font-size:1.25rem;margin:0 0 0.75rem">Plan</h2>
            <p style="font-size:0.9rem;color:var(--muted);margin:0 0 0.75rem">
                → <?= htmlspecialchars((string) ($plan['destination'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </p>
            <ol style="margin:0;padding-left:1.25rem;line-height:1.55;font-size:0.95rem">
                <?php foreach (($plan['operations'] ?? []) as $op): ?>
                    <li style="margin-bottom:0.35rem">
                        <code><?= htmlspecialchars((string) ($op['type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code>
                        — <?= htmlspecialchars((string) ($op['description'] ?? $op['path'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </li>
                <?php endforeach ?>
            </ol>

            <form method="POST" action="/forge/generate" style="margin-top:1.25rem">
                <?= $csrf->field() ?>
                <input type="hidden" name="project_name" value="<?= htmlspecialchars($form['project_name'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="destination" value="<?= htmlspecialchars($form['destination'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="recipe" value="<?= htmlspecialchars($form['recipe'], ENT_QUOTES, 'UTF-8') ?>">
                <label style="display:flex;gap:0.5rem;align-items:flex-start;font-size:0.9rem;margin-bottom:0.85rem">
                    <input type="checkbox" name="confirm" value="1" required>
                    <span>Je confirme la génération greenfield (destination vide).</span>
                </label>
                <button type="submit" class="btn btn-primary" style="cursor:pointer;font:inherit">Générer</button>
            </form>
        </div>
    <?php endif ?>

    <?php if (is_array($report)): ?>
        <div class="auth-card" style="max-width:36rem">
            <h2 style="font-family:Fraunces,Georgia,serif;font-size:1.25rem;margin:0 0 0.75rem">Rapport</h2>
            <p style="margin:0 0 0.75rem;font-size:0.9rem">
                Statut :
                <strong><?= !empty($report['success']) ? 'OK' : 'Échec' ?></strong>
                <?= !empty($report['dryRun']) ? '(dry-run)' : '' ?>
            </p>
            <ul style="margin:0;padding-left:1.25rem;font-size:0.9rem;line-height:1.5">
                <?php foreach (($report['entries'] ?? []) as $entry): ?>
                    <li>
                        [<?= htmlspecialchars((string) ($entry['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>]
                        <?= htmlspecialchars((string) ($entry['operation'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        — <?= htmlspecialchars((string) ($entry['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif ?>
</section>
