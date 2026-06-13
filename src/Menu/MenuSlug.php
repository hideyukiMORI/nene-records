<?php

declare(strict_types=1);

namespace NeNeRecords\Menu;

final class MenuSlug
{
    /** Slugifies a name, falling back to `menu` for symbol-only input. */
    public static function fromName(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug === '' ? 'menu' : $slug;
    }

    /** Returns a slug not already used (per repository), appending -2, -3, … */
    public static function unique(string $base, MenuRepositoryInterface $repository, ?int $excludeId = null): string
    {
        $slug = $base;
        $suffix = 2;
        while ($repository->existsBySlug($slug, $excludeId)) {
            $slug = $base . '-' . $suffix;
            ++$suffix;
        }

        return $slug;
    }
}
