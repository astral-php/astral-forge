# Astral Forge

Générateur de projets **Astral MVC 2.1** — préversion **0.3.1** (`@dev` jusqu’à 1.0.0).

Tutoriel infos : `astral-informations/astral-forge/Dossier-technique/docs/tuto-forge.md`

- Blueprint JSON (schema v1)
- Plan déterministe sans I/O (`php bin/forge plan`)
- Recettes : **blank** · **blog-lite** · **api-json**
- Génération greenfield via `astral-starter:^2.1` + modules
- UI **Vue 3** + **astral-vite** (nova-red) — wizard `/forge`

## Prérequis

- PHP ^8.3
- Composer
- `astral-php/astral-core:^2.1`

## Install

```bash
composer create-project astral-php/astral-forge:^0.1 astral-forge
cd astral-forge
cp .env.example .env
```

Ou clone + `composer install`.

## CLI

```bash
php bin/forge plan resources/blueprints/blank.example.json --dest=../mon-application
php bin/forge generate resources/blueprints/blank.example.json --dest=../mon-application --dry-run
php bin/forge generate resources/blueprints/blank.example.json --dest=../mon-application --confirm
```

> Équivalent console : `php bin/console forge:plan|forge:generate` (nécessite **astral-core ≥ 2.1.2**). `bin/forge` reste le point d’entrée dédié.

La destination doit être sous `FORGE_ALLOWED_ROOT` (défaut : parent du dépôt) ou `sys_get_temp_dir()`.

## Web

```bash
composer serve
# http://127.0.0.1:8090/forge
```

### Front (dev)

```bash
npm install
npm run dev          # Vite :5173 — APP_ENV=development
npm run build        # public/build + manifest.json
```

## Tests

```bash
composer test
```

## Licence

MIT
