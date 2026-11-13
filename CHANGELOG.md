# Changelog — astral-forge

## 1.0.0 — 2026-11-13

### Stable

- Contrat stable = périmètre **0.6.3** QA (Win Laragon C:/D: · Linux WSL) — **sans** nouvelles recettes apps
- Install from-scratch : `forge:env` · `/setup` · `forge:migrate` · openssl · droits Linux documentés
- Atelier : Mes projets · migrate / rollback · module EN singulier · blank / blog-lite / api-json
- Commande : `composer create-project astral-php/astral-forge:^1.0`
- Suite (Nova · recettes forum/shop/hub · Forum next) → **feuille 2.2**

## 0.6.3 — 2026-11-13

### Doc

- Prérequis utilisateur : extension PHP **`openssl`** (README + `/docs` + `.env.example`)
- Linux : droits **création / lecture / modification / suppression** sous `FORGE_ALLOWED_ROOT` pour `www-data` (purge atelier = droits OS, pas bug Forge)

## 0.6.2 — 2026-11-11

### Amélioré

- Détection Laragon **C:\** et **D:\** (+ `LARAGON_ROOT`) pour `FORGE_PHP_BIN` / `FORGE_COMPOSER_BIN`

## 0.6.1 — 2026-11-11

### Corrigé

- Génération : PHP Laragon **sans openssl** (ex. 8.5 nts) cassait `composer create-project` — préférence PHP **avec openssl**, sinon `disable-tls` local dans `COMPOSER_HOME`

## 0.6.0 — 2026-11-11

Install from-scratch Win/Linux — préversion **`@dev`** (pas de news site tant que `^0.6` non stable).

### Ajouté

- `composer forge:env` / `php bin/forge-env` — génère `.env` **production** + détection OS (PHP/Composer) + `FORGE_ALLOWED_ROOT` (parent de Forge)
- Hook `post-create-project-cmd` → `@forge:env`
- `.env.example` aligné 0.6 (production par défaut, chemins documentés)
- Setup web `/setup` — « Installer / Migrer » si migrations Forge en attente ; redirect depuis `/forge` et `/forge/new`
- `composer forge:migrate` → `php bin/console migrate`
- `GET /forge/api/environment` expose `install` + `setup_url`

### Corrigé

- `bin/console` : wiring `ToolchainResolver` + `ComposerCreateProjectRunner` (migrate CLI)

### Amélioré

- Messages **Permission denied** explicites (racine projets + hint Linux www-data)
- `COMPOSER_HOME` : fallback `storage/composer-home` si home FPM non writable ; exposé dans `/forge/api/environment`
- Préflight : racines `FORGE_ALLOWED_ROOT` non writable → erreur claire

### Modules

- Doc UI : nav layout **manuelle** (lien non auto)
- Entité = English **singular** PascalCase ASCII ; table = pluriel ; aperçu live `/forge/api/entity-preview`
- Refus pluriel / accents + suggest singulier (ex. Categories → Category)

### Suite 0.6

- QA create-project Win+Linux · tag **0.6.0 `@dev`** (Packagist)
- **Pas de news** tant que la ligne n’est pas **`^0.6` stable** (correctifs 0.6.x `@dev` d’abord)

## 0.5.0 — 2026-11-07

Atelier post-génération — préversion **`@dev`** (contrat stable = **1.0.0**).

### Ajouté

- Registre SQLite `forge_projects` — upsert après generate OK (sync + job)
- Écran **Mes projets** (`/forge`) · wizard déplacé en `/forge/new`
- **Gérer la base de données** : status (badge À jour / en attente) · Migrer · Rollback · libellés FR
- **Créer un module** : `CrudModuleWriter` + migrate auto ; redirects `RedirectResponse::to()`
- **Supprimer le projet** : efface le dossier (confirm + tooltip irréversible) · distinct de « Retirer du registre »
- Fiche projet réorganisée : hero + panneau Activité · grille Modules / BDD / Créer module · zone dangereuse repliée
- Migrations : libellé court + tooltip nom complet (ellipsis)
- Wizard : bandeau **Configuration OK / requise** (`GET /forge/api/environment` au mount)
- Jobs CLI atelier (`bin/forge-cli-job`) pour migrate / rollback sous Apache Windows

### Corrigé

- Panneau Activité : fin de job → `GET` fiche projet (plus de `reload` sur POST Migrer = boucle)

### Notes

- **A5** (miroir CLI élargi make:*/cache) **reporté** — hors périmètre min. 0.5
- Suite : QA parcours complets → ajustements éventuels en **0.6.x** → **1.0.0**
- Réflexion ouverte : parcours d’**installation Forge** (ex. génération assistée du `.env` avec chemins PHP/Composer)

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
- CTA Forge / « Créer un projet » ; Connexion / Inscription masquées (UX mono-user locale).

### Notes

- Préversion `@dev` · moteur génération inchangé vs 0.3.x (UI/contenu).

## 0.3.1 — 2026-10-31

### Modifié

- UX locale : auth masquée ; CTA Forge.

## 0.3.0 — 2026-10-31

### Ajouté

- Island Vue 3 + astral-vite sur `/forge` (wizard config → plan → confirm).

## 0.2.0 — 2026-10-30

### Ajouté

- Recettes **blog-lite** et **api-json**.

## 0.1.0 — 2026-10-29

### Ajouté

- Scaffold Forge · plan dry-run · génération blank via composer create-project starter.
