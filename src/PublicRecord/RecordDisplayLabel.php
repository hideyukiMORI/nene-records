<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use NeNeRecords\TextField\TextField;

/**
 * Resolves the label shown for a record in a *listing* context — breadcrumbs,
 * child-page lists, JSON-LD — where the record itself is not the page body.
 *
 * PHP twin of the frontend `shared/lib/get-record-display-label.ts`; the order
 * is deliberately identical so the SSR label and the SPA label agree:
 *
 *   title field → entity meta_title → derived excerpt → caller fallback
 *
 * The excerpt step is what makes this class necessary. A bespoke page authored
 * as one html field (bare layout, #799) has no `title` field, so a naive "first
 * non-empty field" fallback puts the page's entire source into a link label:
 * on the public site that dumped 57KB of markup into a breadcrumb, a child link
 * and the JSON-LD BreadcrumbList (#875). The admin listing hit the same bug and
 * fixed it the same way (#849 cap, #853 meta_title) — this is the public half.
 *
 * An explicit `title` field is trusted verbatim; only derived text is capped.
 */
final class RecordDisplayLabel
{
    /** Matches the frontend FALLBACK_LABEL_MAX so both halves cut at the same place. */
    private const DERIVED_MAX = 120;

    /** Strip markup, collapse whitespace, and cap — for use as a derived label. */
    public static function derive(string $value): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags($value)));

        if ($text === '' || mb_strlen($text) <= self::DERIVED_MAX) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, self::DERIVED_MAX)) . '…';
    }

    /**
     * @param list<TextField> $textFieldRows rows for any entities; filtered by $entityId
     * @param string          $fallback      used when nothing else yields text
     */
    public static function resolve(
        int $entityId,
        array $textFieldRows,
        ?string $metaTitle,
        string $fallback,
    ): string {
        foreach ($textFieldRows as $row) {
            if ($row->entityId === $entityId && $row->fieldKey === 'title' && trim($row->value) !== '') {
                return trim($row->value);
            }
        }

        // The SEO title beats the derived excerpt: bespoke pages that share one
        // html field would otherwise all show the same stripped header/nav text
        // (#853 — the same reason the admin listing prefers it).
        if ($metaTitle !== null && trim($metaTitle) !== '') {
            return trim($metaTitle);
        }

        foreach ($textFieldRows as $row) {
            if ($row->entityId === $entityId && trim($row->value) !== '') {
                $derived = self::derive($row->value);
                if ($derived !== '') {
                    return $derived;
                }
            }
        }

        return $fallback;
    }
}
