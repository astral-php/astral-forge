# Changelog — astral-forge

## 0.4.1 — 2026-11-06

### Ajouté

- Résolveur **PHP + Composer** multi-OS (`ToolchainResolver`) — PATH, voisin Laragon `composer.phar`
- Préflight `GET /forge/api/environment` + contrôle avant generate
- Config `FORGE_PHP_BIN` / `FORGE_COMPOSER_BIN` (docs `.env.example`)
- Génération web sous Apache Windows via **job détaché** (`bin/forge-job` + polling UI)
- API `GET /forge/api/jobs/:id` pour suivi async

### Modifié

- `ComposerCreateProjectRunner` : invocation `php` + `composer.phar` ; stdout/stderr fichiers ; env `APPDATA` / `COMPOSER_HOME`
- Sanitisation UTF-8 des erreurs Composer (évite HTTP 500 `JsonException` sous Windows)
- Wizard : destination par défaut `../mon-application` ; suivi job en cours
- Fix : sous Apache, ne plus prendre `PHP_BINARY=httpd.exe` — résolution `php.exe` (PATH / Laragon)

### Notes

- Préversion **`@dev`** jusqu’à **1.0.0**.
- QA web Win validée : **blank** · **blog-lite** · **api-json**.
- Suite : atelier **0.5.0** (registre projets / migrate / module).

## 0.4.0 — 2026-11-01

### Ajouté

- Refonte visuelle **nova-red** (home hero, topbar, cards, wizard).
- Pages contenu : `/templates` · `/docs` · `/discover` · `/extensions` · `/news`.
- Contenu d’usage aligné sur le tutoriel infos (UI + CLI + limites).

### Modifié

- Nav : Accueil · Projets · Templates · Documentation · Extensions.
- Terminal home : commandes réelles `plan` / `generate` (plus de faux `create`).
- Pages contenu centrées (`.content-page`).

### Notes

- Préversion **`@dev`** jusqu’à **1.0.0**.
- Moteur blueprint / plan / generate **inchangé**.
- Parcours de création (plan → generate) : **à valider** en QA avant 1.0.

## 0.3.1 — 2026-10-31

### Modifié

- UX locale mono-user : **Connexion / Inscription** masquées ; CTA **Forge** / « Créer un projet »
- Auth starter conservée (routes directes) mais hors parcours principal

## 0.3.0 — 2026-10-31

### Ajouté

- UI **Vue 3** + **astral-vite ^1.2** sur `/forge` (wizard config → plan → confirm)
- API JSON `POST /forge/api/plan` · `POST /forge/api/generate` (CSRF `X-CSRF-Token`)
- ADR-003 figé : stack Forge = **Vue** (pas React)
- Build prod : `npm run build` (+ copie `manifest.json` pour Vite 5)

### Notes

- Dev front : `npm run dev` (port 5173) + `APP_ENV=development`
- Assets buildés commités sous `public/build/` pour create-project sans npm

## 0.2.0 — 2026-10-31

### Ajouté

- Recettes **blog-lite** (module Article CRUD + liste publique) et **api-json** (`/api/v1/health` + Cors)
- Feature `crud_resource` + `modules[]` (PascalCase)
- Générateurs `CrudModuleWriter` / `ApiBootstrapWriter` (routes `forge_routes.php`, provider généré)
- Exemples blueprint `resources/blueprints/blog-lite.example.json` · `api-json.example.json`
- UI `/forge` : choix de recette

### Notes

- Packages Composer optionnels : `astral-php/astral-form:^0.2` (déclarable, install manuelle)
- Préversion `@dev` jusqu’à 1.0.0

## 0.1.0 — 2026-10-31

### Ajouté

- Scaffold app (starter 2.1) · UI **nova-red**
- Blueprint schema v1 + validateur (clés strictes)
- Planificateur déterministe + exécuteur (greenfield)
- Génération recette **blank** via `composer create-project` starter ^2.1
- CLI `forge:plan` / `forge:generate` (`--dry-run` / `--confirm`)
- UI PHP `/forge` (plan + confirmation)
- Confinement chemins (`SecurePathGuard`)
- Tests unitaires moteur

### Notes

- Préversion : traité comme `@dev` jusqu’à **1.0.0**
- Modules CRUD / API / UI Vite = 0.2 / 0.3
