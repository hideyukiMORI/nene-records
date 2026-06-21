<?php

declare(strict_types=1);

namespace NeNeRecords\BlocksField;

/**
 * Whitelist of post-block types (#486). Each type has a known consumer renderer,
 * an admin inspector form, and a `data` shape validated by
 * {@see BlocksDocumentValidator}. Curated typed blocks (Gutenberg-style column),
 * NOT a free-form page builder. S1 ships `text` and `callout`; later slices add
 * `hero`, `gallery`/`carousel`, and `chart`.
 */
final class BlockTypes
{
    /** @var list<string> */
    private const TYPES = [
        'text',
        'callout',
    ];

    public static function isValid(string $type): bool
    {
        return in_array($type, self::TYPES, true);
    }

    /** @return list<string> */
    public static function all(): array
    {
        return self::TYPES;
    }
}
