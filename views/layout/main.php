<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Astral Forge', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,600;0,9..40,700;1,9..40,400&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --ink: #1f1412;
            --muted: #6b5652;
            --paper: #faf6f5;
            --panel: #ffffff;
            --line: #e8d9d6;
            --accent: #b42318;
            --accent-soft: #fdeceb;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "DM Sans", system-ui, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(1200px 500px at 10% -10%, #f3d9d5 0%, transparent 55%),
                radial-gradient(900px 400px at 100% 0%, #f0e6e3 0%, transparent 50%),
                var(--paper);
        }
        a { color: var(--accent); text-decoration: none; }
        a:hover { text-decoration: underline; }
        .wrap { max-width: 720px; margin: 0 auto; padding: 1.25rem 1.25rem 3rem; }
        header {
            display: flex; align-items: center; justify-content: space-between;
            gap: 1rem; padding: 0.5rem 0 1.5rem; border-bottom: 1px solid var(--line);
        }
        .brand {
            font-family: Fraunces, Georgia, serif;
            font-weight: 700; font-size: 1.25rem; color: var(--ink); text-decoration: none;
        }
        .brand:hover { text-decoration: none; color: var(--accent); }
        nav { display: flex; flex-wrap: wrap; gap: 0.85rem; align-items: center; font-size: 0.9rem; }
        nav a { color: var(--muted); text-decoration: none; }
        nav a:hover { color: var(--accent); }
        .btn {
            display: inline-block; border-radius: 999px; padding: 0.55rem 1rem;
            font-weight: 600; font-size: 0.9rem; border: 1px solid transparent;
        }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { filter: brightness(1.05); text-decoration: none; color: #fff; }
        .btn-ghost { border-color: var(--line); color: var(--ink); background: var(--panel); }
        .btn-ghost:hover { border-color: var(--accent); color: var(--accent); text-decoration: none; }
        main { padding-top: 2rem; }
        .flash-ok, .flash-err {
            border-radius: 12px; padding: 0.75rem 1rem; margin-bottom: 1rem; font-size: 0.9rem;
        }
        .flash-ok { background: #eef6f0; color: #0a4d37; border: 1px solid #b7d9c8; }
        .flash-err { background: var(--accent-soft); color: #8a1f1a; border: 1px solid #f0c2be; }
        footer {
            margin-top: 3rem; padding-top: 1.25rem; border-top: 1px solid var(--line);
            font-size: 0.8rem; color: var(--muted);
        }

        /* Formulaires — même langage que le layout */
        .auth-wrap { max-width: 26rem; margin: 0 auto; }
        .auth-card {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 1.1rem;
            padding: 1.75rem 1.5rem;
            box-shadow: 0 10px 30px rgba(26, 31, 28, 0.04);
        }
        .auth-card h1 {
            font-family: Fraunces, Georgia, serif;
            font-size: 1.65rem;
            font-weight: 700;
            margin: 0 0 0.35rem;
            color: var(--ink);
            text-align: center;
        }
        .auth-card .auth-lead {
            text-align: center;
            color: var(--muted);
            font-size: 0.9rem;
            margin: 0 0 1.5rem;
            line-height: 1.45;
        }
        .auth-mark {
            width: 3.25rem; height: 3.25rem; margin: 0 auto 1rem;
            border-radius: 999px;
            background: var(--accent-soft);
            color: var(--accent);
            display: flex; align-items: center; justify-content: center;
        }
        .auth-mark svg { width: 1.5rem; height: 1.5rem; }
        .form-stack { display: flex; flex-direction: column; gap: 1.1rem; }
        .field label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 0.35rem;
        }
        .field label .hint { font-weight: 400; color: var(--muted); }
        .field-row {
            display: flex; align-items: center; justify-content: space-between;
            gap: 0.75rem; margin-bottom: 0.35rem;
        }
        .field-row label { margin: 0; }
        .input {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 0.85rem;
            padding: 0.65rem 0.9rem;
            font: inherit;
            font-size: 0.9rem;
            color: var(--ink);
            background: #fff;
            transition: border-color .15s, box-shadow .15s;
        }
        .input::placeholder { color: #8a9a91; }
        .input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(15, 107, 76, 0.15);
        }
        .input.is-invalid {
            border-color: #d9776f;
            background: #fdf6f5;
        }
        .field-error { margin: 0.35rem 0 0; font-size: 0.8rem; color: #b42318; }
        .form-errors {
            border-radius: 0.85rem;
            border: 1px solid #f0c2be;
            background: #fdeceb;
            color: #8a1f1a;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
        }
        .form-errors ul { margin: 0.35rem 0 0; padding-left: 1.1rem; }
        .btn-block {
            width: 100%;
            border: none;
            cursor: pointer;
            border-radius: 999px;
            padding: 0.7rem 1rem;
            font: inherit;
            font-weight: 600;
            font-size: 0.95rem;
            background: var(--accent);
            color: #fff;
        }
        .btn-block:hover { filter: brightness(1.06); }
        .auth-footer {
            margin: 1.35rem 0 0;
            text-align: center;
            font-size: 0.9rem;
            color: var(--muted);
        }
        .link-quiet { font-size: 0.8rem; color: var(--accent); }
        .notice-ok {
            display: flex; gap: 0.65rem; align-items: flex-start;
            border-radius: 0.85rem;
            border: 1px solid #b7d9c8;
            background: #eef6f0;
            color: #0a4d37;
            padding: 0.85rem 1rem;
            font-size: 0.875rem;
            line-height: 1.45;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <header>
            <a class="brand" href="/">Astral Forge</a>
            <nav>
                <a href="/forge">Forge</a>
                <?php if ($auth->check()): ?>
                    <span style="color:var(--muted)"><?= htmlspecialchars($auth->name(), ENT_QUOTES, 'UTF-8') ?></span>
                    <a href="/profile">Profil</a>
                    <?php if ($auth->is(\Core\Auth\Role::Admin)): ?>
                        <a href="/admin/users">Admin</a>
                    <?php endif ?>
                    <form method="POST" action="/logout" style="display:inline;margin:0">
                        <?= $csrf->field() ?>
                        <button type="submit" class="btn btn-ghost" style="cursor:pointer;font:inherit">Déconnexion</button>
                    </form>
                <?php else: ?>
                    <a href="/login">Connexion</a>
                    <a class="btn btn-primary" href="/register">Inscription</a>
                <?php endif ?>
            </nav>
        </header>

        <main>
            <?php if ($session->hasFlash('success')): ?>
                <div class="flash-ok"><?= htmlspecialchars((string) $session->getFlash('success'), ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif ?>
            <?php if ($session->hasFlash('error')): ?>
                <div class="flash-err"><?= htmlspecialchars((string) $session->getFlash('error'), ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif ?>

            <?= $content ?? '' ?>
        </main>

        <footer>
            Astral Forge · nova-red · <a href="https://github.com/astral-php/astral-core" target="_blank" rel="noopener">astral-core</a>
        </footer>
    </div>
</body>
</html>
