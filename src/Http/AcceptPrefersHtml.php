<?php

declare(strict_types=1);

namespace NeNeRecords\Http;

/**
 * Should a public-site edge layer answer this request with HTML? (#915)
 *
 * Yes for an explicit `text/html`, for the catch-all wildcard (what plain curl and
 * the major SNS unfurlers — Twitterbot, facebookexternalhit, Slackbot,
 * Discordbot — send), and for a missing Accept header (RFC 7231: no header
 * means the client accepts anything). Only a client that names another type
 * without either token (e.g. `Accept: application/json`) keeps the framework's
 * JSON answer.
 *
 * Requiring the literal `text/html` here is what made the site root unfurl as
 * the NENE2 API index on every SNS while the browser view looked fine.
 */
final class AcceptPrefersHtml
{
    public static function check(string $acceptHeader): bool
    {
        $accept = trim($acceptHeader);

        return $accept === ''
            || str_contains($accept, 'text/html')
            || str_contains($accept, '*/*');
    }
}
