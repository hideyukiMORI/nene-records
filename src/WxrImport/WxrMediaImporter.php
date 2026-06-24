<?php

declare(strict_types=1);

namespace NeNeRecords\WxrImport;

use NeNeRecords\Media\UploadMediaInput;
use NeNeRecords\Media\UploadMediaUseCaseInterface;
use Throwable;

/**
 * Imports WordPress attachment items (post_type=attachment) into the NeNe media
 * library by fetching each file from its original URL and storing it via the
 * media upload use case. Returns a map of original URL → new media URL so the
 * entity importer can rewrite `<img src>` references in the imported content.
 *
 * Files that cannot be fetched, exceed the size cap, or have a disallowed type
 * are skipped (the upload use case validates type/size and throws).
 */
final readonly class WxrMediaImporter
{
    public function __construct(
        private WxrMediaFetcherInterface $fetcher,
        private UploadMediaUseCaseInterface $upload,
    ) {
    }

    public function importAttachments(WxrDocument $document): WxrMediaImportResult
    {
        /** @var array<string, string> $urlMap */
        $urlMap = [];
        $imported = 0;
        $skipped = 0;

        foreach ($document->items as $item) {
            if ($item->postType !== 'attachment') {
                continue;
            }

            $url = $item->attachmentUrl;
            if ($url === null || $url === '' || isset($urlMap[$url])) {
                if ($url === null || $url === '') {
                    ++$skipped;
                }
                continue;
            }

            $fetched = $this->fetcher->fetch($url);
            if ($fetched === null) {
                ++$skipped;
                continue;
            }

            $newUrl = $this->store($fetched);
            if ($newUrl === null) {
                ++$skipped;
                continue;
            }

            $urlMap[$url] = $newUrl;
            ++$imported;
        }

        return new WxrMediaImportResult($imported, $skipped, $urlMap);
    }

    private function store(WxrMediaFetchResult $fetched): ?string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'wxrmedia_');
        if ($tmp === false) {
            return null;
        }

        try {
            if (file_put_contents($tmp, $fetched->bytes) === false) {
                return null;
            }

            return $this->upload->execute(new UploadMediaInput(
                tmpPath: $tmp,
                originalName: $fetched->filename,
                mimeType: $fetched->mimeType,
                size: strlen($fetched->bytes),
            ))->url;
        } catch (Throwable) {
            return null; // disallowed type / too large / storage failure → skip
        } finally {
            @unlink($tmp);
        }
    }
}
