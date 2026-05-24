<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

final class EntityStatus
{
    public const DRAFT = 'draft';
    public const PUBLISHED = 'published';
    public const ARCHIVED = 'archived';

    /** @return list<string> */
    public static function values(): array
    {
        return [self::DRAFT, self::PUBLISHED, self::ARCHIVED];
    }

    public static function isValid(string $status): bool
    {
        return in_array($status, self::values(), true);
    }
}
