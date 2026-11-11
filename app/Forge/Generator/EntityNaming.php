<?php

declare(strict_types=1);

namespace App\Forge\Generator;

use App\Forge\Exception\GenerationException;

/**
 * Nommage modules CRUD : entité PascalCase anglais **singulier** → table pluriel anglais.
 */
final class EntityNaming
{
    /** @var array<string, string> snake singulier → pluriel */
    private const IRREGULAR_PLURAL = [
        'news' => 'news',
        'person' => 'people',
        'child' => 'children',
        'man' => 'men',
        'woman' => 'women',
        'mouse' => 'mice',
        'goose' => 'geese',
        'tooth' => 'teeth',
        'foot' => 'feet',
    ];

    /** @var array<string, string> snake pluriel → singulier */
    private const IRREGULAR_SINGULAR = [
        'news' => 'news',
        'people' => 'person',
        'children' => 'child',
        'men' => 'man',
        'women' => 'woman',
        'mice' => 'mouse',
        'geese' => 'goose',
        'teeth' => 'tooth',
        'feet' => 'foot',
    ];

    /** @var list<string> */
    public const RESERVED = ['User', 'Home', 'Auth', 'Profile', 'Admin', 'Api', 'Forge'];

    /**
     * Valide et normalise une entité (English singular PascalCase ASCII).
     *
     * @throws GenerationException
     */
    public static function assertValidEntity(string $raw): string
    {
        $entity = trim($raw);
        if ($entity === '') {
            throw new GenerationException(
                'Nom d’entité requis — English singular PascalCase (ex. Task, Category, Article).'
            );
        }

        if (preg_match('/[^\x20-\x7E]/', $entity) === 1) {
            throw new GenerationException(
                'English singular only (ASCII) — pas d’accents ni caractères non latins (ex. Category, pas Catégorie).'
            );
        }

        if (!preg_match('/^[A-Z][A-Za-z0-9]*$/', $entity)) {
            throw new GenerationException(
                'PascalCase anglais singulier attendu (ex. Task, Category). Reçu : « ' . $entity . ' ».'
            );
        }

        if (in_array($entity, self::RESERVED, true)) {
            throw new GenerationException(
                'Entité réservée : ' . $entity . '. Choisissez un autre nom (ex. Task, Category).'
            );
        }

        $preview = self::preview($entity);
        if ($preview['looks_plural'] && $preview['suggested_entity'] !== null && $preview['suggested_entity'] !== $entity) {
            throw new GenerationException(
                'Utilisez le singulier anglais : « ' . $preview['suggested_entity'] . ' » '
                . '(table « ' . self::toSnakePlural($preview['suggested_entity']) . ' »). '
                . '« ' . $entity . ' » semble pluriel.'
            );
        }

        return $entity;
    }

    /**
     * @return array{
     *     input: string,
     *     entity: ?string,
     *     table: ?string,
     *     route: ?string,
     *     ok: bool,
     *     looks_plural: bool,
     *     suggested_entity: ?string,
     *     warnings: list<string>,
     *     error: ?string
     * }
     */
    public static function preview(string $raw): array
    {
        $input = trim($raw);
        $warnings = [];

        if ($input === '') {
            return [
                'input' => $input,
                'entity' => null,
                'table' => null,
                'route' => null,
                'ok' => false,
                'looks_plural' => false,
                'suggested_entity' => null,
                'warnings' => [],
                'error' => 'Saisissez une entité (English singular, ex. Category).',
            ];
        }

        if (preg_match('/[^\x20-\x7E]/', $input) === 1) {
            return [
                'input' => $input,
                'entity' => null,
                'table' => null,
                'route' => null,
                'ok' => false,
                'looks_plural' => false,
                'suggested_entity' => null,
                'warnings' => [],
                'error' => 'English singular only (ASCII) — pas d’accents.',
            ];
        }

        $pascal = self::toPascalCandidate($input);
        if ($pascal === null) {
            return [
                'input' => $input,
                'entity' => null,
                'table' => null,
                'route' => null,
                'ok' => false,
                'looks_plural' => false,
                'suggested_entity' => null,
                'warnings' => [],
                'error' => 'PascalCase anglais attendu (lettres/chiffres, majuscule initiale).',
            ];
        }

        if (in_array($pascal, self::RESERVED, true)) {
            return [
                'input' => $input,
                'entity' => $pascal,
                'table' => null,
                'route' => null,
                'ok' => false,
                'looks_plural' => false,
                'suggested_entity' => null,
                'warnings' => [],
                'error' => 'Nom réservé : ' . $pascal,
            ];
        }

        $snake = self::toSnake($pascal);
        $looksPlural = self::looksPluralSnake($snake);
        $suggested = $looksPlural ? self::snakeToPascal(self::singularizeSnake($snake)) : null;

        if ($looksPlural && $suggested !== null && $suggested !== $pascal) {
            $warnings[] = 'Semble pluriel — préférer « ' . $suggested . ' » (table '
                . self::toSnakePlural($suggested) . ').';
        }

        if ($snake === 'new') {
            $warnings[] = 'Évitez « New » — préférez un nom métier (ex. NewsItem, Article).';
        }

        $table = self::toSnakePlural($pascal);

        $isPluralInput = $looksPlural && $suggested !== null && $suggested !== $pascal;

        return [
            'input' => $input,
            'entity' => $pascal,
            'table' => $table,
            'route' => '/' . $table,
            'ok' => !$isPluralInput,
            'looks_plural' => $isPluralInput,
            'suggested_entity' => $isPluralInput ? $suggested : null,
            'warnings' => $warnings,
            'error' => null,
        ];
    }

    public static function toSnakePlural(string $pascal): string
    {
        $snake = self::toSnake($pascal);
        if (isset(self::IRREGULAR_PLURAL[$snake])) {
            return self::IRREGULAR_PLURAL[$snake];
        }
        if (str_ends_with($snake, 'y') && !preg_match('/[aeiou]y$/', $snake)) {
            return substr($snake, 0, -1) . 'ies';
        }
        if (
            str_ends_with($snake, 's')
            || str_ends_with($snake, 'x')
            || str_ends_with($snake, 'z')
            || str_ends_with($snake, 'ch')
            || str_ends_with($snake, 'sh')
        ) {
            return $snake . 'es';
        }

        return $snake . 's';
    }

    public static function toViewFolder(string $pascal): string
    {
        return strtolower((string) preg_replace('/[A-Z]/', '-$0', lcfirst($pascal)));
    }

    private static function toSnake(string $pascal): string
    {
        return strtolower((string) preg_replace('/[A-Z]/', '_$0', lcfirst($pascal)));
    }

    private static function toPascalCandidate(string $input): ?string
    {
        if (preg_match('/^[A-Z][A-Za-z0-9]*$/', $input) === 1) {
            return $input;
        }

        // category → Category ; task_item → TaskItem
        if (preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $input) !== 1) {
            return null;
        }

        $parts = preg_split('/_+/', $input) ?: [];
        $out = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $out .= ucfirst(strtolower($part));
        }

        return $out !== '' && preg_match('/^[A-Z][A-Za-z0-9]*$/', $out) === 1 ? $out : null;
    }

    private static function snakeToPascal(string $snake): string
    {
        $parts = explode('_', $snake);
        $out = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $out .= ucfirst($part);
        }

        return $out;
    }

    private static function looksPluralSnake(string $snake): bool
    {
        if (isset(self::IRREGULAR_SINGULAR[$snake]) && self::IRREGULAR_SINGULAR[$snake] !== $snake) {
            return true;
        }

        // Invariants / singuliers courants en -s
        if (in_array($snake, ['news', 'series', 'species', 'status', 'address', 'process', 'access', 'success'], true)) {
            return false;
        }

        $singular = self::singularizeSnake($snake);
        if ($singular === $snake) {
            return false;
        }

        // Round-trip : plural(singular) === pluriel saisi
        return self::toSnakePlural(self::snakeToPascal($singular)) === $snake;
    }

    private static function singularizeSnake(string $snake): string
    {
        if (isset(self::IRREGULAR_SINGULAR[$snake])) {
            return self::IRREGULAR_SINGULAR[$snake];
        }
        if (str_ends_with($snake, 'ies') && strlen($snake) > 4) {
            return substr($snake, 0, -3) . 'y';
        }
        if (
            str_ends_with($snake, 'ses')
            || str_ends_with($snake, 'xes')
            || str_ends_with($snake, 'zes')
            || str_ends_with($snake, 'ches')
            || str_ends_with($snake, 'shes')
        ) {
            return substr($snake, 0, -2);
        }
        if (preg_match('/(us|is|ss|os|as)$/', $snake) === 1) {
            return $snake;
        }
        if (str_ends_with($snake, 's') && strlen($snake) > 3) {
            return substr($snake, 0, -1);
        }

        return $snake;
    }
}
