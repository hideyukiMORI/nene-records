<?php

declare(strict_types=1);

namespace NeNeRecords\Layout;

/**
 * Whitelist of public-page layout presets (Phase 1: scaffold selection).
 *
 * Layout is a typed, whitelisted value decoupled from content: it controls the
 * page scaffold (width / chrome / theme), not the content itself. Restricting
 * to a fixed set keeps the contract portable across tenants and rejects
 * arbitrary values at the write boundary.
 *
 * - standard: header / single column (max-w-3xl) / footer — the default.
 * - full:     header / full-width content / footer — landing-page oriented.
 * - bare:     no header/footer, no theme — fully custom page (CSS in content).
 */
final class PublicLayouts
{
    public const DEFAULT = 'standard';

    /** @var list<string> */
    private const LAYOUTS = ['standard', 'full', 'bare'];

    public static function isValid(string $layout): bool
    {
        return in_array($layout, self::LAYOUTS, true);
    }

    /** @return list<string> */
    public static function all(): array
    {
        return self::LAYOUTS;
    }

    /**
     * Resolve the effective layout: per-entity override wins, then the type's
     * default, then the global default.
     */
    public static function resolve(?string $entityLayout, ?string $typeDefaultLayout): string
    {
        if ($entityLayout !== null && self::isValid($entityLayout)) {
            return $entityLayout;
        }

        if ($typeDefaultLayout !== null && self::isValid($typeDefaultLayout)) {
            return $typeDefaultLayout;
        }

        return self::DEFAULT;
    }
}
