<?php

declare(strict_types=1);

namespace NeNeRecords\BlocksField;

/**
 * Whitelist of post-block types (#486). Each type has a known consumer renderer,
 * an admin inspector form, and a `data` shape validated by
 * {@see BlocksDocumentValidator}. Curated typed blocks (Gutenberg-style column),
 * NOT a free-form page builder. S1 ships `text` and `callout`, S2 adds `hero`,
 * S4 adds `gallery`, S5 adds `chart`, and records-embed 案1 adds `contact-form` (#1030).
 *
 * Adding a type here is safe for existing documents: unknown types are rejected on
 * write ({@see BlocksDocumentValidator}) and dropped on read, so no stored document
 * changes meaning when the list grows.
 */
final class BlockTypes
{
    /** @var list<string> */
    private const TYPES = [
        'text',
        'callout',
        'hero',
        'gallery',
        'chart',
        'group',
        'columns',
        'spacer',
        'divider',
        'contact-form',
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
