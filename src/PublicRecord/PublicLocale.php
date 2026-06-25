<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

/**
 * Public content locales (#540). Mirrors the frontend's `SupportedLocale` set.
 * The public site negotiates content per request via `?lang=`; an absent or
 * unsupported value resolves to `null` (serve the base / locale-agnostic rows).
 */
final class PublicLocale
{
    /** @var list<string> */
    public const SUPPORTED = ['en', 'ja', 'fr', 'zh-Hans', 'pt-BR', 'de'];

    /** `<html lang>` when no locale is negotiated (base content). */
    public const DEFAULT_LANG = 'ja';

    private function __construct()
    {
    }

    /** A supported locale id, or null when absent / unsupported. */
    public static function resolve(?string $requested): ?string
    {
        $value = trim((string) $requested);

        return in_array($value, self::SUPPORTED, true) ? $value : null;
    }
}
