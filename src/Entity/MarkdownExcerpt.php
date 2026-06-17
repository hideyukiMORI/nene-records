<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

/**
 * Derives a plain-text excerpt from a markdown body: strips the common markdown
 * syntax to readable text, collapses whitespace, and truncates to a length.
 * Deliberately lightweight (no full markdown parse) — it only needs to produce a
 * teaser, not faithful rendering.
 */
final class MarkdownExcerpt
{
    public static function fromMarkdown(string $markdown, int $length): string
    {
        return self::truncate(self::stripMarkdown($markdown), $length);
    }

    /** Trim plain text to at most `length` chars, adding an ellipsis when cut. */
    public static function truncate(string $text, int $length): string
    {
        $text = trim($text);
        if ($length <= 0 || mb_strlen($text) <= $length) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $length)) . '…';
    }

    private static function stripMarkdown(string $markdown): string
    {
        $s = $markdown;
        $s = preg_replace('/```.*?```/s', ' ', $s) ?? $s;        // fenced code
        $s = preg_replace('/`([^`]*)`/', '$1', $s) ?? $s;        // inline code
        $s = preg_replace('/!\[([^\]]*)\]\([^)]*\)/', '$1', $s) ?? $s; // images → alt
        $s = preg_replace('/\[([^\]]*)\]\([^)]*\)/', '$1', $s) ?? $s;  // links → text
        $s = preg_replace('/^\s{0,3}(#{1,6}|>|[-*+]|\d+\.)\s+/m', '', $s) ?? $s; // block markers
        $s = preg_replace('/[*_~]+/', '', $s) ?? $s;             // emphasis
        $s = preg_replace('/<[^>]+>/', '', $s) ?? $s;            // html tags
        $s = preg_replace('/\s+/', ' ', $s) ?? $s;               // collapse whitespace

        return trim($s);
    }
}
