<?php

declare(strict_types=1);

namespace NeNeRecords\WxrImport;

/**
 * Builds a dry-run {@see WxrImportPlan} from a parsed {@see WxrDocument}, applying
 * the WordPress → NeNe Records mapping:
 *   - post_type: post → `posts`, page → `pages` (others skipped)
 *   - status:    publish → published, draft/pending/private → draft, future → scheduled
 *                (trash / auto-draft / inherit etc. skipped)
 *   - categories + post_tags → NeNe tags (merged)
 *   - missing slug → derived from the title (warned)
 */
final class WxrImportPlanner
{
    private const POST_TYPE_MAP = [
        'post' => 'posts',
        'page' => 'pages',
    ];

    private const STATUS_MAP = [
        'publish' => 'published',
        'draft' => 'draft',
        'pending' => 'draft',
        'private' => 'draft',
        'future' => 'scheduled',
    ];

    /** SEO title postmeta keys, in precedence order (Yoast → RankMath → AIOSEO). */
    private const SEO_TITLE_KEYS = ['_yoast_wpseo_title', 'rank_math_title', '_aioseo_title', '_aioseop_title'];

    /** SEO meta-description postmeta keys, same precedence. */
    private const SEO_DESCRIPTION_KEYS = ['_yoast_wpseo_metadesc', 'rank_math_description', '_aioseo_description', '_aioseop_description'];

    public function plan(WxrDocument $document): WxrImportPlan
    {
        $planned = [];
        $skipped = [];
        $warnings = [];
        $tagSet = [];
        $countsByType = [];
        $countsByStatus = [];

        foreach ($document->items as $item) {
            $label = $item->title !== '' ? $item->title : ($item->slug ?? '(無題)');

            $entityType = self::POST_TYPE_MAP[$item->postType] ?? null;
            if ($entityType === null) {
                // Attachments are not entities; they are imported into the media
                // library on execute (and body image URLs are rewritten).
                $reason = $item->postType === 'attachment'
                    ? '添付ファイル（実行時にメディアとして取り込み）'
                    : "未対応の post_type: {$item->postType}";
                $skipped[] = new WxrImportSkippedItem($label, $reason);
                continue;
            }

            $status = self::STATUS_MAP[$item->status] ?? null;
            if ($status === null) {
                $skipped[] = new WxrImportSkippedItem($label, "未対応の status: {$item->status}");
                continue;
            }

            $slug = $item->slug ?? self::slugify($item->title);
            if ($item->slug === null) {
                $warnings[] = "「{$label}」は slug が無いためタイトルから生成します: {$slug}";
            }
            if ($slug === '') {
                $skipped[] = new WxrImportSkippedItem($label, 'slug を決定できません（タイトルも空）');
                continue;
            }

            $tags = array_values(array_unique([...$item->categorySlugs, ...$item->tagSlugs]));
            foreach ($tags as $tag) {
                $tagSet[$tag] = true;
            }

            $planned[] = new WxrImportPlannedItem(
                $item->title,
                $slug,
                $entityType,
                $status,
                $tags,
                $item->contentHtml,
                $item->publishedAtIso,
                $item->originalLink,
                self::seoValue($item->postMeta, self::SEO_TITLE_KEYS),
                self::seoValue($item->postMeta, self::SEO_DESCRIPTION_KEYS),
            );
            $countsByType[$entityType] = ($countsByType[$entityType] ?? 0) + 1;
            $countsByStatus[$status] = ($countsByStatus[$status] ?? 0) + 1;
        }

        return new WxrImportPlan(
            plannedItems: $planned,
            skippedItems: $skipped,
            tagSlugs: array_keys($tagSet),
            warnings: $warnings,
            countsByEntityType: $countsByType,
            countsByStatus: $countsByStatus,
        );
    }

    /**
     * First non-empty SEO value among the given postmeta keys, ignoring
     * unexpanded plugin templates (e.g. Yoast `%%title%%`, RankMath `%title%`)
     * which would render as literal garbage — those fall back to NeNe defaults.
     *
     * @param array<string, string> $postMeta
     * @param list<string>          $keys
     */
    private static function seoValue(array $postMeta, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = trim($postMeta[$key] ?? '');
            if ($value !== '' && !self::looksLikeTemplate($value)) {
                return $value;
            }
        }

        return null;
    }

    private static function looksLikeTemplate(string $value): bool
    {
        return str_contains($value, '%%') || preg_match('/%[a-z][a-z0-9_]*%/i', $value) === 1;
    }

    private static function slugify(string $title): string
    {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $slug) ?? '';

        return trim($slug, '-');
    }
}
