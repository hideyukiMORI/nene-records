<?php

declare(strict_types=1);

namespace NeNeRecords\WxrImport;

use finfo;

/**
 * Fetches WordPress attachment files over HTTP(S) during a WXR import.
 *
 * Only http/https URLs are fetched (an admin-supplied WXR could otherwise point
 * at arbitrary schemes). The endpoint is admin-only; private-network egress
 * hardening (SSRF) is left to deployment-level controls.
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

        $context = stream_context_create([
            'http' => [
                'timeout' => self::TIMEOUT_SECONDS,
                'follow_location' => 1,
                'max_redirects' => 3,
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
}
