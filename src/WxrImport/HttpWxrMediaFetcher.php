<?php

declare(strict_types=1);

namespace NeNeRecords\WxrImport;

use finfo;

/**
 * Fetches WordPress attachment files over HTTP(S) during a WXR import.
 *
 * SSRF hardening (the WXR is admin-supplied but an org admin is not infra-trusted
 * in a multi-tenant deployment):
 *   - only http/https schemes are fetched;
 *   - the host is resolved up-front and every resolved A/AAAA address must be
 *     publicly routable — private, loopback, link-local (incl. the cloud
 *     metadata endpoint 169.254.169.254) and reserved ranges are rejected
 *     without any network call;
 *   - HTTP redirects are NOT followed, so a public URL cannot bounce the request
 *     to an internal address after the check.
 *
 * Residual DNS-rebinding (resolve-then-connect TOCTOU) is left to deployment-level
 * egress controls.
 */
final readonly class HttpWxrMediaFetcher implements WxrMediaFetcherInterface
{
    private const TIMEOUT_SECONDS = 15;
    private const MAX_BYTES = 10 * 1024 * 1024; // mirrors UploadMediaUseCase cap

    public function fetch(string $url): ?WxrMediaFetchResult
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if ($scheme !== 'http' && $scheme !== 'https') {
            return null;
        }

        if (!$this->hostIsPubliclyRoutable($url)) {
            return null;
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => self::TIMEOUT_SECONDS,
                'follow_location' => 0, // never follow a redirect into an internal address
                'max_redirects' => 0,
                'user_agent' => 'NeNeRecords-WXR-Importer',
                'ignore_errors' => true,
            ],
        ]);

        $bytes = @file_get_contents($url, false, $context, 0, self::MAX_BYTES + 1);
        if ($bytes === false || $bytes === '' || strlen($bytes) > self::MAX_BYTES) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        $filename = is_string($path) && $path !== '' ? basename($path) : 'attachment';

        return new WxrMediaFetchResult(
            bytes: $bytes,
            mimeType: (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes) ?: 'application/octet-stream',
            filename: $filename,
        );
    }

    /** Resolve the URL's host and require every resolved address to be public. */
    private function hostIsPubliclyRoutable(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return false;
        }

        $host = trim($host, '[]'); // strip IPv6 brackets

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return self::isPublicIp($host); // literal IP host — no DNS needed
        }

        $addresses = $this->resolve($host);
        if ($addresses === []) {
            return false; // unresolvable → refuse
        }

        foreach ($addresses as $ip) {
            if (!self::isPublicIp($ip)) {
                return false;
            }
        }

        return true;
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

    /** True only for globally routable addresses (rejects private + reserved ranges). */
    public static function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) !== false;
    }
}
