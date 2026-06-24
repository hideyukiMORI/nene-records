<?php

declare(strict_types=1);

namespace NeNeRecords\WxrImport;

/**
 * A single parsed `<item>` from a WordPress WXR export. Content is kept as the
 * raw `content:encoded` HTML for the downstream importer to map; status/type are
 * the raw WordPress values (mapping to NeNe entity types/statuses happens later).
 */
final readonly class WxrItem
{
    /**
     * @param list<string> $categorySlugs WP category nicenames assigned to this item
     * @param list<string> $tagSlugs      WP post_tag slugs assigned to this item
     */
    public function __construct(
        public ?int $wpPostId,
        public string $postType,
        public string $status,
        public string $title,
        public ?string $slug,
        public string $contentHtml,
        public string $excerptHtml,
        public ?string $publishedAtIso,
        public ?string $originalLink,
        public array $categorySlugs,
        public array $tagSlugs,
        public ?string $attachmentUrl = null, // wp:attachment_url (post_type=attachment)
    ) {
    }
}
