<?php

declare(strict_types=1);

namespace App\Forge\Support;

/**
 * Normalise les chaînes pour json_encode (sortie Composer Windows souvent CP1252).
 */
final class Utf8
{
    public static function stringify(string $value): string
    {
        if ($value === '' || mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        foreach (['CP1252', 'Windows-1252', 'ISO-8859-1', 'CP850'] as $from) {
            $converted = @iconv($from, 'UTF-8//IGNORE', $value);
            if (is_string($converted) && $converted !== '' && mb_check_encoding($converted, 'UTF-8')) {
                return $converted;
            }
        }

        $fallback = @iconv('UTF-8', 'UTF-8//IGNORE', $value);

        return is_string($fallback) ? $fallback : '';
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public static function deep(mixed $value): mixed
    {
        if (is_string($value)) {
            return self::stringify($value);
        }

        if (is_array($value)) {
            $out = [];
            foreach ($value as $key => $item) {
                $out[is_string($key) ? self::stringify($key) : $key] = self::deep($item);
            }

            return $out;
        }

        return $value;
    }
}
