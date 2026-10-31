<!-- Vue : admin/users/index — Gestion des rôles utilisateurs -->

<!-- En-tête -->
<div class="flex items-center justify-between mb-8">
    <div>
        <div class="flex items-center gap-3 mb-1">
            <span class="text-xs font-semibold bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full border border-amber-200">
                Administration
            </span>
        </div>
        <h1 class="text-3xl font-bold text-gray-900">
            <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
        </h1>
        <p class="text-sm text-gray-400 mt-1">
            <?= count($users) ?> utilisateur<?= count($users) > 1 ? 's' : '' ?> —
            <?= $adminCount ?> administrateur<?= $adminCount > 1 ? 's' : '' ?>
        </p>
    </div>
    <a href="/users"
       class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-indigo-600 transition">
        ← Retour à la liste
    </a>
</div>

<!-- Avertissement dernier admin -->
<?php if ($adminCount === 1): ?>
    <div class="mb-6 flex items-start gap-3 bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-xl px-4 py-3">
        <svg class="w-4 h-4 shrink-0 mt-0.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
        <span>
            Il n'y a qu'un seul administrateur. Vous ne pouvez pas le rétrograder tant qu'aucun autre administrateur n'a été désigné.
        </span>
    </div>
<?php endif ?>

<?php if (empty($users)): ?>
    <div class="bg-white rounded-2xl border border-dashed border-gray-200 p-16 text-center text-gray-400">
        Aucun utilisateur enregistré.
    </div>
<?php else: ?>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Utilisateur</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">E-mail</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Rôle actuel</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Changer le rôle</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Inscrit le</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($users as $user): ?>
                <?php
                    $isSelf      = $user->id === $auth->id();
                    $isLastAdmin = $user->role === \Core\Auth\Role::Admin->value && $adminCount <= 1;
                    $isDisabled  = $isSelf || $isLastAdmin;
                ?>
                <tr class="hover:bg-gray-50 transition <?= $isSelf ? 'bg-indigo-50/40' : '' ?>">

                    <!-- Utilisateur -->
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold shrink-0">
                                <?= mb_strtoupper(mb_substr(htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8'), 0, 1)) ?>
                            </span>
                            <div>
                                <p class="font-medium text-gray-800 text-sm">
                                    <?= htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8') ?>
                                    <?php if ($isSelf): ?>
                                        <span class="ml-1 text-xs text-indigo-400 font-normal">(vous)</span>
                                    <?php endif ?>
                                </p>
                                <p class="text-xs text-gray-400 font-mono">#<?= $user->id ?></p>
                            </div>
                        </div>
                    </td>

                    <!-- E-mail -->
                    <td class="px-6 py-4 text-sm text-gray-500">
                        <?= htmlspecialchars($user->email, ENT_QUOTES, 'UTF-8') ?>
                    </td>

                    <!-- Rôle actuel -->
                    <td class="px-6 py-4">
                        <?php
                        $badgeClass = match ($user->role) {
                            'admin' => 'bg-amber-100 text-amber-700 border border-amber-200',
                            'user'  => 'bg-indigo-50 text-indigo-600 border border-indigo-100',
                            default => 'bg-gray-100 text-gray-500 border border-gray-200',
                        };
                        ?>
                        <span class="inline-flex items-center text-xs font-semibold px-2.5 py-0.5 rounded-full <?= $badgeClass ?>">
                            <?= htmlspecialchars(ucfirst($user->role), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>

                    <!-- Formulaire de changement de rôle -->
                    <td class="px-6 py-4">
                        <?php if ($isDisabled): ?>
                            <span class="text-xs text-gray-400 italic">
                                <?php if ($isSelf): ?>
                                    Votre propre rôle
                                <?php elseif ($isLastAdmin): ?>
                                    Dernier admin
                                <?php endif ?>
                            </span>
                        <?php else: ?>
                            <form method="POST"
                                  action="/admin/users/<?= $user->id ?>/role"
                                  class="flex items-center gap-2">
                                <?= $csrf->field() ?>
                                <select name="role"
                                        class="text-sm border border-gray-200 rounded-lg px-2 py-1.5 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 transition">
                                    <?php foreach ($roles as $r): ?>
                                        <?php if ($r === \Core\Auth\Role::Guest->value) continue; ?>
                                        <option value="<?= htmlspecialchars($r, ENT_QUOTES, 'UTF-8') ?>"
                                            <?= $user->role === $r ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(ucfirst($r), ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach ?>
                                </select>
                                <button type="submit"
                                        class="text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg transition shadow-sm">
                                    Appliquer
                                </button>
                            </form>
                        <?php endif ?>
                    </td>

                    <!-- Date d'inscription -->
                    <td class="px-6 py-4 text-xs text-gray-400">
                        <?= htmlspecialchars($user->created_at, ENT_QUOTES, 'UTF-8') ?>
                    </td>

                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
<?php endif ?>
