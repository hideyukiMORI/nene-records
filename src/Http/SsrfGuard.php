<?php

declare(strict_types=1);

namespace NeNeRecords\Http;

/**
 * SSRF guard for outbound HTTP(S) requests to caller/tenant-supplied URLs.
 *
 * An org admin is not infra-trusted in a multi-tenant deployment, so any URL they
 * supply (webhook endpoints, WXR media) must not be allowed to reach internal
 * infrastructure or the cloud metadata endpoint. This guard:
 *
 *   - only accepts http/https schemes (rejects file/ftp/gopher/dict/...);
 *   - resolves the host up-front and requires every resolved A/AAAA address to be
 *     publicly routable — private (10/8, 172.16/12, 192.168/16), loopback
 *     (127/8, ::1), link-local incl. the metadata endpoint 169.254.169.254,
 *     IPv6 unique-local (fc00::/7), carrier-grade NAT (100.64/10, RFC 6598) and
 *     the other reserved ranges are rejected without any network call;
 *   - returns the verified addresses so the caller can pin the connection to them
 *     (redirects must additionally be disabled by the caller so a public URL
 *     cannot bounce to an internal address after the check).
 *
 * Residual DNS-rebinding (a resolve-then-connect TOCTOU) is closed by pinning the
 * connection to {@see SsrfInspection::$addresses}; NAT64-embedded literals and
 * deployment-level egress remain the operator's responsibility.
 *
 * Wave1 note: this is the minimal records-local validator called for in the
 * upstream HTTP-client design (_work/reports/2026-07-06/upstream-design/02-http-client.md §6).
 * When the Nene2\Http\Client base ships its shared SsrfGuard, absorb this into it.
 */
final class SsrfGuard
{
    /** @var list<string> */
    private const ALLOWED_SCHEMES = ['http', 'https'];

    /** Start of the carrier-grade NAT range 100.64.0.0/10 (RFC 6598). */
    private const CGN_NETWORK = '100.64.0.0';

    /** Mask for a /10 prefix (255.192.0.0). */
    private const CGN_MASK = 0xFFC00000;

    /**
     * Inspect an outbound URL, resolving its host and verifying every resolved
     * address is publicly routable. No network egress happens on rejection.
     */
    public function inspect(string $url): SsrfInspection
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!is_string($scheme) || !in_array(strtolower($scheme), self::ALLOWED_SCHEMES, true)) {
            return SsrfInspection::reject('URL scheme must be http or https.');
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return SsrfInspection::reject('URL has no host.');
        }

        $host = trim($host, '[]'); // strip IPv6 brackets

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            // Literal IP host — no DNS lookup required.
            return self::isPublicIp($host)
                ? SsrfInspection::allow([$host])
                : SsrfInspection::reject('URL host is a non-public IP address.');
        }

        $addresses = $this->resolve($host);
        if ($addresses === []) {
            return SsrfInspection::reject('URL host could not be resolved.');
        }

        foreach ($addresses as $ip) {
            if (!self::isPublicIp($ip)) {
                return SsrfInspection::reject('URL host resolves to a non-public IP address.');
            }
        }

        return SsrfInspection::allow($addresses);
    }

    /**
     * Cheap, DNS-free pre-check for request-time validation: the scheme must be
     * http/https and any literal-IP host must be public. Hostnames pass here and
     * are authoritatively re-checked (with resolution + pinning) at egress by
     * {@see inspect()}.
     */
    public function isLikelySafeUrl(string $url): bool
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!is_string($scheme) || !in_array(strtolower($scheme), self::ALLOWED_SCHEMES, true)) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return false;
        }

        $host = trim($host, '[]');

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return self::isPublicIp($host);
        }

        return true; // hostname — deferred to inspect() at egress
    }

    /** True only for globally routable addresses (rejects private + reserved + CGN). */
    public static function isPublicIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        // PHP's reserved-range flag does not cover carrier-grade NAT (100.64.0.0/10).
        return !self::isCarrierGradeNat($ip);
    }

    private static function isCarrierGradeNat(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return false;
        }

        $long = ip2long($ip);
        if ($long === false) {
            return false;
        }

        return ($long & self::CGN_MASK) === (ip2long(self::CGN_NETWORK) & self::CGN_MASK);
    }

    /** @return list<string> resolved IPv4 + IPv6 addresses for the host */
    private function resolve(string $host): array
    {
        $addresses = [];

        $v4 = gethostbynamel($host);
        if ($v4 !== false) {
            $addresses = $v4;
        }

        foreach (@dns_get_record($host, DNS_AAAA) ?: [] as $record) {
            if (isset($record['ipv6']) && is_string($record['ipv6'])) {
                $addresses[] = $record['ipv6'];
            }
        }

        return array_values(array_unique($addresses));
    }
}
