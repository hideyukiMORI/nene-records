<?php

declare(strict_types=1);

namespace NeNeRecords\Http;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Resolves the originating client IP for rate-limiting / abuse-prevention keys.
 *
 * Behind our single trusted reverse proxy (Caddy holds 443 and is the only
 * ingress — the app container publishes no ports), `REMOTE_ADDR` is the proxy's
 * address and is identical for every visitor, so keying a limit on it alone would
 * collapse all clients into one bucket. Caddy records the address it accepted the
 * connection from as the *last* entry of `X-Forwarded-For`; any entries to its
 * left are client-supplied and may be spoofed. We therefore take the last
 * `X-Forwarded-For` hop (the one our proxy appended) and fall back to
 * `REMOTE_ADDR` when no forwarded header is present (direct / local / dev).
 *
 * This mirrors the proxy-header trust already relied on by
 * {@see \NeNeRecords\Auth\SessionCookie::isSecureRequest()} (X-Forwarded-Proto).
 *
 * Assumes exactly one trusted proxy. If a CDN or extra load balancer is ever
 * placed in front of Caddy, the trustworthy hop shifts and this must be revisited.
 */
final class ClientIp
{
    public static function resolve(ServerRequestInterface $request): string
    {
        $forwarded = $request->getHeaderLine('X-Forwarded-For');

        if ($forwarded !== '') {
            $hops = array_values(array_filter(
                array_map('trim', explode(',', $forwarded)),
                static fn (string $hop): bool => $hop !== '',
            ));

            if ($hops !== []) {
                return $hops[array_key_last($hops)];
            }
        }

        $remoteAddr = $request->getServerParams()['REMOTE_ADDR'] ?? '';

        return is_string($remoteAddr) && $remoteAddr !== '' ? $remoteAddr : 'unknown';
    }
}
