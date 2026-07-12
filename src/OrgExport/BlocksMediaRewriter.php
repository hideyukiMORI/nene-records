<?php

declare(strict_types=1);

namespace NeNeRecords\OrgExport;

/**
 * Rewrites the media references embedded in a blocks document during org transport
 * (#795). Two settings/fields carry the exact same block-document shape — a
 * `blocks_fields.value` and the `home_hero` setting (see
 * {@see \NeNeRecords\BlocksField\BlocksDocumentValidator} for the canonical shape).
 *
 * Media-bearing blocks:
 *  - hero:    data.media   = {mediaId, url, alt}
 *  - gallery: data.items[] = {mediaId, url, alt, caption}
 * and containers nest leaf blocks: group → data.children[], columns → data.columns[].children[].
 *
 * On each media reference:
 *  - `mediaId` (a numeric string) is remapped through the media id map so it points
 *    at the imported media row.
 *  - `url` is RELATIVIZED: an absolute same-origin `/media/...` URL loses its
 *    scheme+host and becomes a bare `/media/...` path. Transport is domain-agnostic
 *    (the CLI import does not know the final public domain; Tier A switches DNS
 *    later), and the media file keeps its storage_key, so the relative path resolves
 *    on the target. External images (non-/media paths or other hosts) are left as-is.
 *
 * Blocks carry no numeric entity-id references (internal links are permalink-based
 * and permalinks are preserved; relations live in entity_relations, remapped
 * separately), so only media is rewritten here.
 *
 * A document with no rewritten reference is returned byte-for-byte verbatim (no
 * reformatting churn), and any string that is not a blocks document is left verbatim
 * (defensive: never corrupt an unexpected shape).
 */
final class BlocksMediaRewriter
{
    /**
     * @param array<int, int> $mediaMap old media id → new media id
     */
    public static function rewrite(string $json, array $mediaMap): string
    {
        if ($json === '' || $mediaMap === []) {
            return $json;
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded) || !array_is_list($decoded)) {
            // A blocks document is a JSON array of blocks; anything else is left verbatim.
            return $json;
        }

        $rewritten = array_map(
            static fn (mixed $block): mixed => self::rewriteBlock($block, $mediaMap),
            $decoded,
        );

        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        $after = json_encode($rewritten, $flags);
        if ($after === false) {
            return $json;
        }

        // Re-encode the original the same way: equal output ⇒ nothing was rewritten,
        // so return the untouched body (avoids reformatting a verbatim document).
        return $after === json_encode($decoded, $flags) ? $json : $after;
    }

    /**
     * @param array<int, int> $mediaMap
     */
    private static function rewriteBlock(mixed $block, array $mediaMap): mixed
    {
        if (!is_array($block)) {
            return $block;
        }

        $type = $block['type'] ?? null;
        $data = $block['data'] ?? null;
        if (!is_array($data)) {
            return $block;
        }

        if ($type === 'hero' && isset($data['media']) && is_array($data['media'])) {
            $data['media'] = self::rewriteMedia($data['media'], $mediaMap);
        } elseif ($type === 'gallery' && isset($data['items']) && is_array($data['items'])) {
            $data['items'] = array_map(
                static fn (mixed $item): mixed => is_array($item) ? self::rewriteMedia($item, $mediaMap) : $item,
                $data['items'],
            );
        } elseif ($type === 'group' && isset($data['children']) && is_array($data['children'])) {
            $data['children'] = array_map(
                static fn (mixed $child): mixed => self::rewriteBlock($child, $mediaMap),
                $data['children'],
            );
        } elseif ($type === 'columns' && isset($data['columns']) && is_array($data['columns'])) {
            $data['columns'] = array_map(
                static function (mixed $column) use ($mediaMap): mixed {
                    if (is_array($column) && isset($column['children']) && is_array($column['children'])) {
                        $column['children'] = array_map(
                            static fn (mixed $child): mixed => self::rewriteBlock($child, $mediaMap),
                            $column['children'],
                        );
                    }

                    return $column;
                },
                $data['columns'],
            );
        }

        $block['data'] = $data;

        return $block;
    }

    /**
     * Rewrite a single {mediaId, url, …} media object (hero media or gallery item).
     *
     * @param array<array-key, mixed> $media
     * @param array<int, int>         $mediaMap
     * @return array<array-key, mixed>
     */
    private static function rewriteMedia(array $media, array $mediaMap): array
    {
        $mediaId = $media['mediaId'] ?? null;
        if (is_string($mediaId) && $mediaId !== '' && ctype_digit($mediaId)) {
            $new = $mediaMap[(int) $mediaId] ?? null;
            if ($new !== null) {
                $media['mediaId'] = (string) $new;
            }
        }

        $url = $media['url'] ?? null;
        if (is_string($url) && $url !== '') {
            $media['url'] = self::relativizeMediaUrl($url);
        }

        return $media;
    }

    /**
     * Strip scheme+host from an absolute same-origin `/media/...` URL. A relative
     * URL, or an absolute URL whose path is not under /media/, is returned unchanged.
     */
    private static function relativizeMediaUrl(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return $url; // already relative
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path) || !str_starts_with($path, '/media/')) {
            return $url; // external (non-media) absolute URL — leave verbatim
        }

        $query = parse_url($url, PHP_URL_QUERY);

        return is_string($query) && $query !== '' ? $path . '?' . $query : $path;
    }
}
