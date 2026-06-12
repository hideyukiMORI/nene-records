<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

use RuntimeException;

/**
 * Filesystem-backed {@see StorageInterface}. Objects live under $root and are
 * served by {@see ServeMediaHandler} at $urlPrefix (default "/media").
 */
final readonly class LocalStorage implements StorageInterface
{
    private string $root;
    private string $urlPrefix;

    public function __construct(string $root, string $urlPrefix = '/media')
    {
        $this->root = rtrim($root, '/');
        $this->urlPrefix = '/' . trim($urlPrefix, '/');
    }

    public function writeFromUpload(string $key, string $tmpPath): void
    {
        $dest = $this->resolve($key);
        $dir = dirname($dest);

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Failed to create media directory: ' . $dir);
        }

        // Fall back to copy() in test environments where the source is not an
        // actual HTTP upload and move_uploaded_file() therefore refuses it.
        if (!move_uploaded_file($tmpPath, $dest) && !copy($tmpPath, $dest)) {
            throw new RuntimeException('Failed to move uploaded file to: ' . $dest);
        }
    }

    public function write(string $key, string $contents): void
    {
        $dest = $this->resolve($key);
        $dir = dirname($dest);

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Failed to create media directory: ' . $dir);
        }

        if (file_put_contents($dest, $contents) === false) {
            throw new RuntimeException('Failed to write media object: ' . $key);
        }
    }

    public function exists(string $key): bool
    {
        return is_file($this->resolve($key));
    }

    /**
     * @return resource
     */
    public function readStream(string $key)
    {
        $handle = @fopen($this->resolve($key), 'rb');

        if ($handle === false) {
            throw new RuntimeException('Failed to open media object: ' . $key);
        }

        return $handle;
    }

    public function delete(string $key): void
    {
        $path = $this->resolve($key);

        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function size(string $key): int
    {
        $size = @filesize($this->resolve($key));

        return $size === false ? 0 : $size;
    }

    public function mimeType(string $key): string
    {
        $mime = @mime_content_type($this->resolve($key));

        return $mime === false ? 'application/octet-stream' : $mime;
    }

    public function publicUrl(string $key): string
    {
        return $this->urlPrefix . '/' . ltrim($key, '/');
    }

    public function keyFromUrl(string $url): string
    {
        $prefix = $this->urlPrefix . '/';

        if (str_starts_with($url, $prefix)) {
            return substr($url, strlen($prefix));
        }

        return ltrim($url, '/');
    }

    /**
     * Resolve a storage key to an absolute path, rejecting traversal attempts.
     */
    private function resolve(string $key): string
    {
        $key = ltrim($key, '/');

        if ($key === '' || str_contains($key, '..')) {
            throw new RuntimeException('Invalid media storage key: ' . $key);
        }

        return $this->root . '/' . $key;
    }
}
