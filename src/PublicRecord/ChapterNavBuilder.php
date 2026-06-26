<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

/**
 * Builds the derived chapter navigation shared by the public record view and the
 * preview view. Keeping it here means both use-cases hide the same reserved field
 * keys and resolve sibling URLs identically.
 */
final readonly class ChapterNavBuilder
{
    /**
     * Reserved field keys that drive the chapter navigation. They are structural
     * metadata (which work / which position), never rendered as ordinary record
     * fields — both the SSR display fields and the SPA field list hide them.
     *
     * @var list<string>
     */
    public const FIELD_KEYS = ['series', 'chapter_no', 'chapter_total'];

    /**
     * Build the chapter navigation for a record. Returns null unless the record
     * carries a non-empty `series` (the 目次/index slug) plus a `chapter_no`
     * within a `chapter_total` (>= 1). Sibling URLs are resolved exactly like the
     * canonical permalink, so a work using a slug permalink (`/{type}/{slug}`)
     * yields crawlable URLs for the whole work with no extra fetch.
     */
    public static function build(
        ?string $permalinkPattern,
        string $typeSlug,
        ?string $series,
        ?int $chapterNo,
        ?int $chapterTotal,
    ): ?PublicRecordChapterNav {
        $series = $series === null ? '' : trim($series);

        if ($series === '' || $chapterNo === null || $chapterTotal === null) {
            return null;
        }

        if ($chapterTotal < 1 || $chapterNo < 1 || $chapterNo > $chapterTotal) {
            return null;
        }

        $resolve = static fn (string $slug): string => PublicPermalinkResolver::resolve(
            $permalinkPattern,
            $typeSlug,
            $slug,
            0,
            null,
        );

        return new PublicRecordChapterNav(
            indexUrl: $resolve($series),
            prevUrl: $chapterNo > 1 ? $resolve($series . '-' . ($chapterNo - 1)) : null,
            nextUrl: $chapterNo < $chapterTotal ? $resolve($series . '-' . ($chapterNo + 1)) : null,
            chapterNo: $chapterNo,
            chapterTotal: $chapterTotal,
        );
    }

    /**
     * Serialise the navigation for the JSON bootstrap / API response.
     *
     * @return array{indexUrl: string, prevUrl: ?string, nextUrl: ?string, chapterNo: int, chapterTotal: int}|null
     */
    public static function toBootstrapArray(?PublicRecordChapterNav $nav): ?array
    {
        if ($nav === null) {
            return null;
        }

        return [
            'indexUrl' => $nav->indexUrl,
            'prevUrl' => $nav->prevUrl,
            'nextUrl' => $nav->nextUrl,
            'chapterNo' => $nav->chapterNo,
            'chapterTotal' => $nav->chapterTotal,
        ];
    }
}
