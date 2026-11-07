<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Projet généré enregistré dans l’atelier Forge (SQLite local).
 */
final class ForgeProject
{
    public int $id = 0;
    public string $name = '';
    public string $path = '';
    public string $recipe = 'blank';
    public string $created_at = '';
    public string $updated_at = '';

    public function directoryExists(): bool
    {
        return $this->path !== '' && is_dir($this->path);
    }

    public function basename(): string
    {
        return basename(str_replace('\\', '/', $this->path));
    }
}
