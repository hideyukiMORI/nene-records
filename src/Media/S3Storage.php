<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

use AsyncAws\S3\S3Client;
use RuntimeException;

/**
 * S3-compatible {@see StorageInterface} (AWS S3, MinIO, Cloudflare R2, ...).
 *
 * Objects live in $bucket under an optional $prefix. Because clients fetch
 * objects directly from the bucket/CDN, {@see publicUrl()} returns an absolute
 * URL built from $publicBaseUrl rather than the app's /media route.
 */
final readonly class S3Storage implements StorageInterface
{
    private string $publicBaseUrl;
    private string $prefix;

    public function __construct(
        private S3Client $client,
        private string $bucket,
        string $publicBaseUrl,
        string $prefix = '',
    ) {
        $this->publicBaseUrl = rtrim($publicBaseUrl, '/');
        $this->prefix = $prefix === '' ? '' : trim($prefix, '/') . '/';
    }

    public function writeFromUpload(string $key, string $tmpPath): void
    {
        $body = @fopen($tmpPath, 'rb');

        if ($body === false) {
            throw new RuntimeException('Failed to open upload for S3 put: ' . $tmpPath);
        }

        $mime = mime_content_type($tmpPath);

        $this->client->putObject([
            'Bucket' => $this->bucket,
            'Key' => $this->object($key),
            'Body' => $body,
            'ContentType' => $mime === false ? 'application/octet-stream' : $mime,
        ])->resolve();
    }

    public function exists(string $key): bool
    {
        return $this->client->objectExists([
            'Bucket' => $this->bucket,
            'Key' => $this->object($key),
        ])->isSuccess();
    }

    /**
     * @return resource
     */
    public function readStream(string $key)
    {
        $result = $this->client->getObject([
            'Bucket' => $this->bucket,
            'Key' => $this->object($key),
        ]);

        return $result->getBody()->getContentAsResource();
    }

    public function delete(string $key): void
    {
        $this->client->deleteObject([
            'Bucket' => $this->bucket,
            'Key' => $this->object($key),
        ])->resolve();
    }

    public function size(string $key): int
    {
        $length = $this->client->headObject([
            'Bucket' => $this->bucket,
            'Key' => $this->object($key),
        ])->getContentLength();

        return $length ?? 0;
    }

    public function mimeType(string $key): string
    {
        return $this->client->headObject([
            'Bucket' => $this->bucket,
            'Key' => $this->object($key),
        ])->getContentType() ?? 'application/octet-stream';
    }

    public function publicUrl(string $key): string
    {
        return $this->publicBaseUrl . '/' . $this->object($key);
    }

    public function keyFromUrl(string $url): string
    {
        $base = $this->publicBaseUrl . '/';
        $object = str_starts_with($url, $base) ? substr($url, strlen($base)) : ltrim($url, '/');

        if ($this->prefix !== '' && str_starts_with($object, $this->prefix)) {
            return substr($object, strlen($this->prefix));
        }

        return $object;
    }

    /** Map a storage key to the full object key inside the bucket. */
    private function object(string $key): string
    {
        return $this->prefix . ltrim($key, '/');
    }
}
