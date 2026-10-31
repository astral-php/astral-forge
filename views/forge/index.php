<?php
/** Mount point Vue — wizard config / plan / confirm */
?>
<div id="forge-app" data-csrf="<?= htmlspecialchars($csrf->token(), ENT_QUOTES, 'UTF-8') ?>">
    <p class="text-sm" style="color:var(--muted)">Chargement de l’UI Forge…</p>
</div>
<p class="text-sm mt-6" style="color:var(--muted)">
    Nouveau projet Astral — CLI : <code>php bin/forge plan|generate …</code>
</p>
