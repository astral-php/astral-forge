# Astral Forge

Générateur de projets **Astral MVC 2.1** — préversion **0.6.3** (`@dev` jusqu’à 1.0.0).

Tutoriel infos : `astral-informations/astral-forge/Dossier-technique/docs/tuto-forge.md`  
Pages locales : `/docs` · `/templates` · `/discover` · `/extensions` · `/news`

- Blueprint JSON (schema v1)
- Plan déterministe sans I/O (`php bin/forge plan`)
- Recettes : **blank** · **blog-lite** · **api-json**
- Génération greenfield via `astral-starter:^2.1` + modules
- UI **Vue 3** + **astral-vite** (nova-red) — wizard `/forge/new`
- **Atelier** : Mes projets · migrate / rollback · créer un module · supprimer projet
- **Install 0.6** : `forge:env` + migrate CLI / setup web `/setup`

## Prérequis

- PHP ^8.3
- Composer (`composer` / `composer.phar` joignable depuis le **même** process que le serveur web)
- `astral-php/astral-core:^2.1`
- `proc_open` activé (pas dans `disable_functions`)

Sous **Laragon**, ajoutez Laragon au PATH (php, composer, git, node…). Si Apache n’hérite pas du PATH utilisateur, définissez dans `.env` (ou lancez `composer forge:env`) :

```env
FORGE_PHP_BIN=D:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe
FORGE_COMPOSER_BIN=D:\laragon\bin\composer\composer.phar
```

Forge préfère l’invocation **`php` + `composer.phar`** (évite les shims `.bat` sous Windows).

**Apache Windows (Laragon)** : la génération web lance un **job en arrière-plan** (`bin/forge-job`) pour éviter le crash `mpm_winnt` — l’utilisateur reste dans l’interface.  
Contournement sync expérimental : `FORGE_ALLOW_APACHE_COMPOSER=1`.

Préflight : `GET /forge/api/environment` (toolchain + état migrations Forge).

## Install

```bash
composer create-project astral-php/astral-forge:^0.6 astral-forge
cd astral-forge
# post-create lance déjà forge:env ; sinon :
composer forge:env
composer forge:migrate
# ou UI : ouvrir /setup → « Installer / Migrer »
```

Checklist **Windows (Laragon)** : PHP/Composer PATH ou `FORGE_*_BIN` · `FORGE_ALLOWED_ROOT` = parent (ex. `D:\laragon\www` ou `C:\laragon\www`) · PHP CLI avec extension **`openssl`** · `APP_ENV=production` pour CSS `/build`.

Checklist **Linux** (nginx / php-fpm) :

- `FORGE_ALLOWED_ROOT` = dossier parent des projets (ex. `/home/user/www`)
- L’utilisateur PHP (**souvent `www-data`**) doit pouvoir **créer, lire, modifier et supprimer** sous cette racine
- Modèle recommandé (groupe `www-data`, sticky bit sur les dossiers) :

```bash
sudo chgrp -R www-data /home/user/www
sudo usermod -aG www-data "$USER"   # si vous générez aussi en CLI
sudo find /home/user/www -type d -exec chmod 2775 {} \;
sudo find /home/user/www -type f -exec chmod 664 {} \;
```

- Sans **écriture sur les dossiers** (bit `w` groupe), la génération peut réussir puis la **purge atelier** échoue (`Impossible de supprimer le fichier…`) — ce n’est **pas** un bug Forge, c’est un droit OS
- `COMPOSER_HOME` writable (Forge utilise `storage/composer-home` en secours)
- `FORGE_ALLOW_APACHE_COMPOSER=1` souvent OK sous FPM/nginx
- Extension PHP **`openssl`** requise pour Composer / Packagist HTTPS

Ou clone + `composer install` puis `forge:env` + `forge:migrate`.

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
