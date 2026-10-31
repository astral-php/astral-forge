<?php

declare(strict_types=1);

namespace App\Forge\Blueprint;

use App\Forge\Exception\InvalidBlueprintException;

/**
 * Valide un blueprint JSON (schemaVersion 1) — refuse les clés inconnues.
 */
final class BlueprintValidator
{
    private const ALLOWED_TOP = ['schemaVersion', 'project', 'recipe', 'features', 'modules', 'views', 'packages'];
    private const ALLOWED_PROJECT = ['name', 'target'];
    private const ALLOWED_FEATURES = ['authentication', 'admin_users', 'api', 'crud_resource'];
    private const ALLOWED_VIEWS = ['engine'];
    private const ALLOWED_PACKAGES = [
        'astral-php/astral-form:^0.2',
    ];

    /**
     * @param list<string> $supportedRecipes
     * @param list<string> $supportedTargets
     */
    public function __construct(
        private readonly array $supportedRecipes = ['blank', 'blog-lite', 'api-json'],
        private readonly array $supportedTargets = ['astral-starter-2.1'],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function validate(array $data): Blueprint
    {
        $this->assertOnlyKeys($data, self::ALLOWED_TOP, 'racine');

        $schemaVersion = $data['schemaVersion'] ?? null;
        if ($schemaVersion !== 1) {
            throw new InvalidBlueprintException('schemaVersion doit être 1.');
        }

        $project = $data['project'] ?? null;
        if (!is_array($project)) {
            throw new InvalidBlueprintException('project est requis et doit être un objet.');
        }
        $this->assertOnlyKeys($project, self::ALLOWED_PROJECT, 'project');

        $name = $project['name'] ?? null;
        if (!is_string($name) || !preg_match('/^[a-z][a-z0-9-]{0,62}$/', $name)) {
            throw new InvalidBlueprintException(
                'project.name invalide : kebab-case minuscule (ex. mon-application).'
            );
        }

        $target = $project['target'] ?? 'astral-starter-2.1';
        if (!is_string($target) || !in_array($target, $this->supportedTargets, true)) {
            throw new InvalidBlueprintException(
                'project.target non supporté. Attendus : ' . implode(', ', $this->supportedTargets)
            );
        }

        $recipe = $data['recipe'] ?? 'blank';
        if (!is_string($recipe) || !in_array($recipe, $this->supportedRecipes, true)) {
            throw new InvalidBlueprintException(
                'recipe non supportée. Attendues : ' . implode(', ', $this->supportedRecipes)
            );
        }

        $featuresRaw = $data['features'] ?? [];
        if (!is_array($featuresRaw)) {
            throw new InvalidBlueprintException('features doit être un objet.');
        }
        $this->assertOnlyKeys($featuresRaw, self::ALLOWED_FEATURES, 'features');

        $features = [
            'authentication' => $this->boolFlag($featuresRaw, 'authentication', true),
            'admin_users' => $this->boolFlag($featuresRaw, 'admin_users', true),
            'api' => $this->boolFlag($featuresRaw, 'api', $recipe === 'api-json'),
            'crud_resource' => $this->boolFlag($featuresRaw, 'crud_resource', $recipe === 'blog-lite'),
        ];

        if ($recipe === 'blank' && $features['api'] === true) {
            throw new InvalidBlueprintException(
                'features.api=true nécessite la recette api-json (pas blank).'
            );
        }
        if ($recipe === 'api-json' && $features['api'] !== true) {
            throw new InvalidBlueprintException('La recette api-json exige features.api=true.');
        }
        if ($recipe === 'blog-lite' && $features['crud_resource'] !== true) {
            throw new InvalidBlueprintException('La recette blog-lite exige features.crud_resource=true.');
        }

        $modules = $this->normalizeModules($data['modules'] ?? null, $recipe, $features['crud_resource']);

        $views = $data['views'] ?? ['engine' => 'php'];
        if (!is_array($views)) {
            throw new InvalidBlueprintException('views doit être un objet.');
        }
        $this->assertOnlyKeys($views, self::ALLOWED_VIEWS, 'views');
        $engine = $views['engine'] ?? 'php';
        if ($engine !== 'php') {
            throw new InvalidBlueprintException('views.engine doit être "php" (twide différé).');
        }

        $packages = $data['packages'] ?? [];
        if (!is_array($packages)) {
            throw new InvalidBlueprintException('packages doit être un tableau de chaînes.');
        }
        foreach ($packages as $pkg) {
            if (!is_string($pkg) || $pkg === '') {
                throw new InvalidBlueprintException('Chaque package doit être une chaîne non vide.');
            }
            if (preg_match('/astral-core[^\\s]*\\^?1\\b/', $pkg) === 1) {
                throw new InvalidBlueprintException('astral-core ^1 refusé — cible 2.1 uniquement.');
            }
            if (!in_array($pkg, self::ALLOWED_PACKAGES, true)) {
                throw new InvalidBlueprintException(
                    'Package non autorisé en 0.2 : ' . $pkg . ' (autorisés : '
                    . implode(', ', self::ALLOWED_PACKAGES) . ').'
                );
            }
        }
        /** @var list<string> $packages */

        return new Blueprint(
            schemaVersion: 1,
            projectName: $name,
            target: $target,
            recipe: $recipe,
            features: $features,
            viewsEngine: 'php',
            packages: $packages,
            modules: $modules,
        );
    }

    /**
     * @param mixed $raw
     * @return list<string>
     */
    private function normalizeModules(mixed $raw, string $recipe, bool $crud): array
    {
        if ($raw === null) {
            if ($recipe === 'blog-lite' || $crud) {
                return ['Article'];
            }

            return [];
        }
        if (!is_array($raw)) {
            throw new InvalidBlueprintException('modules doit être un tableau de noms PascalCase.');
        }

        $modules = [];
        foreach ($raw as $item) {
            if (is_array($item)) {
                $item = $item['name'] ?? null;
            }
            if (!is_string($item) || !preg_match('/^[A-Z][a-zA-Z0-9]*$/', $item)) {
                throw new InvalidBlueprintException(
                    'Chaque module doit être un nom PascalCase (ex. Article).'
                );
            }
            $modules[] = $item;
        }

        if (($recipe === 'blog-lite' || $crud) && $modules === []) {
            $modules = ['Article'];
        }

        if ($modules !== [] && !$crud && $recipe !== 'blog-lite') {
            throw new InvalidBlueprintException(
                'modules non vides exige features.crud_resource=true ou recipe blog-lite.'
            );
        }

        return array_values(array_unique($modules));
    }

    /**
     * @param array<string, mixed> $data
     * @param list<string> $allowed
     */
    private function assertOnlyKeys(array $data, array $allowed, string $context): void
    {
        foreach (array_keys($data) as $key) {
            if (!is_string($key) || !in_array($key, $allowed, true)) {
                throw new InvalidBlueprintException(
                    sprintf('Clé inconnue "%s" dans %s.', (string) $key, $context)
                );
            }
        }
    }

    /**
     * @param array<string, mixed> $features
     */
    private function boolFlag(array $features, string $key, bool $default): bool
    {
        if (!array_key_exists($key, $features)) {
            return $default;
        }
        $value = $features[$key];
        if (!is_bool($value)) {
            throw new InvalidBlueprintException("features.{$key} doit être un booléen.");
        }

        return $value;
    }
}
