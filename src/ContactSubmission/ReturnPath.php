<?php

declare(strict_types=1);

namespace NeNeRecords\ContactSubmission;

/**
 * Where the visitor is sent back to after posting the form.
 *
 * The value arrives in a hidden field, so it is attacker-controlled: a public endpoint that
 * redirects anywhere it is told is an open redirect, useful for making a phishing link look
 * like it came from this site. Only a same-origin *path* is accepted — never an absolute URL,
 * never a protocol-relative `//host`, never a backslash authority that some browsers resolve
 * cross-origin.
 */
final class ReturnPath
{
    private const FALLBACK = '/';

    private const MAX_LENGTH = 2000;

    public static function sanitize(mixed $raw): string
    {
        if (!is_string($raw)) {
            return self::FALLBACK;
        }

        $path = trim($raw);

        if ($path === '' || strlen($path) > self::MAX_LENGTH) {
            return self::FALLBACK;
        }

        // Must be a rooted path, and the second character must not turn it into an authority.
        if (!str_starts_with($path, '/') || preg_match('~^[/\\\\]{2}~', $path) === 1) {
            return self::FALLBACK;
        }

        // A control character would let the value break out of the Location header.
        if (preg_match('/[\x00-\x1F\x7F]/', $path) === 1) {
            return self::FALLBACK;
        }

        return $path;
    }

    /**
     * Appends the outcome marker the page reads to show "sent" or "could not send".
     */
    public static function withOutcome(string $path, string $outcome): string
    {
        $separator = str_contains($path, '?') ? '&' : '?';

        return $path . $separator . 'contact=' . $outcome;
    }
}
