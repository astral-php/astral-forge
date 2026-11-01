<?php
/** Mount point Vue — wizard config / plan / confirm */
?>
<div id="forge-app" data-csrf="<?= htmlspecialchars($csrf->token(), ENT_QUOTES, 'UTF-8') ?>">
    <p class="text-sm" style="color:var(--muted)">Chargement de l’UI Forge…</p>
</div>
<p class="text-sm mt-6" style="color:var(--muted);margin-top:1.25rem">
    CLI : <code>php bin/forge plan|generate …</code>
    · <a href="/docs" style="color:var(--accent);font-weight:600">Documentation</a>
    · <a href="/templates" style="color:var(--accent);font-weight:600">Templates</a>
</p>
