<?php

declare(strict_types=1);

namespace NeNeRecords\Auth;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Builds the Set-Cookie header for the session token.
 *
 * The token lives in an HttpOnly cookie so page JavaScript can never read it
 * (XSS can't steal the session). SameSite=Lax + a required custom request header
 * on cookie-authenticated mutations (see AdminApiAuthMiddleware) defends against
 * CSRF. `Secure` is added only over HTTPS so the cookie still works on http
 * localhost in development.
 */
final class SessionCookie
{
    public const NAME = 'nene_session';

    public static function build(string $token, int $maxAgeSeconds, bool $secure): string
    {
        $maxAge = max(0, $maxAgeSeconds);

        $parts = [
            self::NAME . '=' . $token,
            'Path=/',
            'HttpOnly',
            'SameSite=Lax',
            'Max-Age=' . $maxAge,
        ];

        if ($secure) {
            $parts[] = 'Secure';
        }

        return implode('; ', $parts);
    }

    /** A Set-Cookie value that immediately expires the session cookie. */
    public static function clear(bool $secure): string
    {
        return self::build('', 0, $secure);
    }

    /** True when the request arrived over HTTPS (directly or via a trusted proxy). */
    public static function isSecureRequest(ServerRequestInterface $request): bool
    {
        if (strtolower($request->getUri()->getScheme()) === 'https') {
            return true;
        }

        return strtolower(trim($request->getHeaderLine('X-Forwarded-Proto'))) === 'https';
    }
}
