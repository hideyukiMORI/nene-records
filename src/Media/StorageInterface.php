<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

/**
 * Abstraction over the physical storage backing the media library.
 *
 * Keys are storage-relative paths such as "2026/06/abcdef.png" (no leading
 * slash). Each driver owns the mapping between a key and the public URL that
 * clients use to fetch the object ({@see publicUrl()} / {@see keyFromUrl()} are
 * inverses), so callers never need to know whether storage is local disk, an
 * S3-compatible bucket, or anything else.
 */
interface StorageInterface
{
    /**
     * Move a freshly uploaded temp file into storage at $key.
     *
     * @throws \RuntimeException when the file cannot be stored.
     */
    public function writeFromUpload(string $key, string $tmpPath): void;

    /**
     * Write raw bytes to $key (used for cached image derivatives).
     *
     * @throws \RuntimeException when the object cannot be written.
     */
    public function write(string $key, string $contents): void;

    public function exists(string $key): bool;

    /**
     * Open a read stream for $key. The caller owns the returned resource.
     *
     * @return resource
     *
     * @throws \RuntimeException when the object cannot be opened.
     */
    public function readStream(string $key);

    /**
     * Remove the object at $key. Best-effort: a missing object is not an error.
     */
    public function delete(string $key): void;

    public function size(string $key): int;

    public function mimeType(string $key): string;

    /** Public URL clients use to fetch the object at $key. */
    public function publicUrl(string $key): string;

    /** Inverse of {@see publicUrl()}: recover the storage key from a stored URL. */
    public function keyFromUrl(string $url): string;
}
