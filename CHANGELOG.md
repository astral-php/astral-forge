# Changelog — astral-forge

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
