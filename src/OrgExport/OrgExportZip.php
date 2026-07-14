<?php

declare(strict_types=1);

namespace NeNeRecords\OrgExport;

use RuntimeException;
use ZipArchive;

/**
 * Single-file org transport archive (#798).
 *
 * Layout:
 *   export.json          the DB payload ({@see OrgExportPayloadBuilder})
 *   media/<storage_key>  each media original, at its storage-relative path
 *
 * Only originals are bundled — image derivatives are regenerated on demand by
 * {@see \NeNeRecords\Media\ServeDerivativeHandler} on the target, so they never
 * need to travel. Originals are added with ZipArchive::addFile (streamed from
 * disk) and extracted through the zip:// stream wrapper (streamed to disk), so a
 * large media library is never held in memory in full. Local-disk storage only:
 * with the S3 driver the originals do not live under $mediaRoot and the caller
 * must refuse the zip flow (see tools/export-org.php / tools/import-org.php).
 */
final class OrgExportZip
{
    private const PAYLOAD_ENTRY = 'export.json';
    private const MEDIA_PREFIX  = 'media/';

    /**
     * Write $payload plus its media originals to a new zip at $zipPath.
     *
     * @param  array<string, mixed>                        $payload    export payload (must contain "media")
     * @param  string                                      $mediaRoot  absolute path to var/media
     * @return array{added: int, missing: list<string>}                originals bundled / storage keys not found on disk
     */
    public static function create(string $zipPath, array $payload, string $mediaRoot): array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Failed to create archive: ' . $zipPath);
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($json === false) {
            $zip->close();
            throw new RuntimeException('Failed to encode export payload as JSON.');
        }
        if (!$zip->addFromString(self::PAYLOAD_ENTRY, $json)) {
            $zip->close();
            throw new RuntimeException('Failed to add export.json to the archive.');
        }

        $root    = rtrim($mediaRoot, '/');
        $added   = 0;
        $missing = [];
        foreach (self::storageKeys($payload) as $key) {
            $src = self::safePath($root, $key);
            if ($src === null || !is_file($src)) {
                $missing[] = $key;
                continue;
            }
            if (!$zip->addFile($src, self::MEDIA_PREFIX . $key)) {
                $zip->close();
                throw new RuntimeException('Failed to add media file to the archive: ' . $key);
            }
            $added++;
        }

        if ($zip->close() !== true) {
            throw new RuntimeException('Failed to finalize archive: ' . $zipPath);
        }

        return ['added' => $added, 'missing' => $missing];
    }

    /**
     * Read $zipPath: decode its payload and place each media original under
     * $mediaRoot, preserving the storage_key relative path.
     *
     * @param  string                                                        $mediaRoot  absolute path to var/media
     * @return array{payload: array<string, mixed>, placed: int, missing: list<string>}
     */
    public static function open(string $zipPath, string $mediaRoot): array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Failed to open archive: ' . $zipPath);
        }

        $raw = $zip->getFromName(self::PAYLOAD_ENTRY);
        if ($raw === false) {
            $zip->close();
            throw new RuntimeException('Archive does not contain export.json — not an org-export zip.');
        }
        $zip->close();

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new RuntimeException('export.json is not valid JSON: ' . $e->getMessage(), 0, $e);
        }
        if (!is_array($decoded) || !isset($decoded['meta'])) {
            throw new RuntimeException('export.json is not an org-export payload (missing "meta").');
        }
        /** @var array<string, mixed> $payload */
        $payload = $decoded;

        $root    = rtrim($mediaRoot, '/');
        $placed  = 0;
        $missing = [];
        foreach (self::storageKeys($payload) as $key) {
            $entry = self::MEDIA_PREFIX . $key;
            $dest  = self::safePath($root, $key);
            if ($dest === null) {
                $missing[] = $key;
                continue;
            }

            $stream = @fopen('zip://' . $zipPath . '#' . $entry, 'rb');
            if ($stream === false) {
                $missing[] = $key;
                continue;
            }

            $dir = dirname($dest);
            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                fclose($stream);
                throw new RuntimeException('Failed to create media directory: ' . $dir);
            }

            $out = @fopen($dest, 'wb');
            if ($out === false) {
                fclose($stream);
                throw new RuntimeException('Failed to write media file: ' . $dest);
            }
            stream_copy_to_stream($stream, $out);
            fclose($stream);
            fclose($out);
            $placed++;
        }

        return ['payload' => $payload, 'placed' => $placed, 'missing' => $missing];
    }

    /**
     * Distinct, non-empty storage keys referenced by the payload's media rows.
     *
     * @param  array<string, mixed> $payload
     * @return list<string>
     */
    private static function storageKeys(array $payload): array
    {
        $keys = [];
        foreach ((array) ($payload['media'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $key = isset($row['storage_key']) ? ltrim((string) $row['storage_key'], '/') : '';
            if ($key !== '') {
                $keys[$key] = true;
            }
        }

        return array_keys($keys);
    }

    /**
     * Resolve $key under $root, rejecting traversal. Returns null for unsafe keys.
     */
    private static function safePath(string $root, string $key): ?string
    {
        $key = ltrim($key, '/');
        if ($key === '' || str_contains($key, '..') || str_contains($key, "\0")) {
            return null;
        }

        return $root . '/' . $key;
    }
}
