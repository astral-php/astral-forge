<?php

declare(strict_types=1);

namespace App\Forge\Runtime;

/**
 * Sonde un binaire PHP CLI (ex. openssl requis par Composer).
 */
final class PhpBinaryProbe
{
    /**
     * @return 'yes'|'no'|'unknown'
     */
    public static function opensslStatus(string $phpBin): string
    {
        $phpBin = trim($phpBin);
        if ($phpBin === '') {
            return extension_loaded('openssl') ? 'yes' : 'no';
        }

        if (
            defined('PHP_BINARY')
            && is_string(PHP_BINARY)
            && PHP_BINARY !== ''
            && self::sameBinary($phpBin, PHP_BINARY)
        ) {
            return extension_loaded('openssl') ? 'yes' : 'no';
        }

        if (!function_exists('proc_open') || (!self::isBare($phpBin) && !is_file($phpBin))) {
            return 'unknown';
        }

        $code = 'echo extension_loaded("openssl") ? "1" : "0";';
        $cmd = [$phpBin, '-r', $code];

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = @proc_open($cmd, $descriptors, $pipes, null, null, ['bypass_shell' => true]);
        if (!is_resource($process)) {
            return 'unknown';
        }

        fclose($pipes[0]);
        $stdout = trim((string) stream_get_contents($pipes[1]));
        $stderr = trim((string) stream_get_contents($pipes[2]));
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);

        if ($exit !== 0 && $stdout !== '0' && $stdout !== '1') {
            return 'unknown';
        }

        if ($stdout === '1') {
            return 'yes';
        }
        if ($stdout === '0') {
            return 'no';
        }

        // Binaire non exécutable (stub de test, etc.)
        return $stderr !== '' || $exit !== 0 ? 'unknown' : 'unknown';
    }

    public static function hasOpenssl(string $phpBin): bool
    {
        return self::opensslStatus($phpBin) === 'yes';
    }

    /**
     * @param list<string> $candidates
     */
    public static function firstWithOpenssl(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if ($candidate !== '' && self::hasOpenssl($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private static function sameBinary(string $a, string $b): bool
    {
        $ra = realpath($a);
        $rb = realpath($b);
        if ($ra !== false && $rb !== false) {
            return $ra === $rb;
        }

        return str_replace('\\', '/', strtolower($a)) === str_replace('\\', '/', strtolower($b));
    }

    private static function isBare(string $path): bool
    {
        return !str_contains($path, '/') && !str_contains($path, '\\') && !str_contains($path, ':');
    }
}
