<?php

declare(strict_types=1);

namespace NeNeRecords\WxrImport;

use SimpleXMLElement;

/**
 * Parses a WordPress WXR (eXtended RSS) export into a {@see WxrDocument}.
 * Namespace prefixes (`wp:`, `content:`, `excerpt:`) are resolved via SimpleXML's
 * prefixed-children lookup so any WXR version (1.0–1.2) works.
 */
final class WxrParser
{
    public function parse(string $xml): WxrDocument
    {
        if (trim($xml) === '') {
            throw new WxrParseException('Empty WXR payload.');
        }

        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            $root = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NOCDATA);
        } finally {
            libxml_use_internal_errors($previous);
        }

        if ($root === false) {
            throw new WxrParseException('WXR payload is not well-formed XML.');
        }

        $channel = $root->channel;

        if (!$channel instanceof SimpleXMLElement) {
            throw new WxrParseException('WXR payload has no <channel> (not a WordPress export?).');
        }

        $siteTitle = trim((string) $channel->title);
        $baseUrl = trim((string) $channel->children('wp', true)->base_site_url);
        if ($baseUrl === '') {
            $baseUrl = trim((string) $channel->link);
        }

        $terms = [];
        foreach ($channel->children('wp', true)->category as $category) {
            $terms[] = new WxrTerm(
                'category',
                trim((string) $category->children('wp', true)->category_nicename),
                trim((string) $category->children('wp', true)->cat_name),
            );
        }
        foreach ($channel->children('wp', true)->tag as $tag) {
            $terms[] = new WxrTerm(
                'tag',
                trim((string) $tag->children('wp', true)->tag_slug),
                trim((string) $tag->children('wp', true)->tag_name),
            );
        }

        $items = [];
        foreach ($channel->item as $item) {
            $items[] = $this->parseItem($item);
        }

        return new WxrDocument($siteTitle, $baseUrl, $items, $terms);
    }

    private function parseItem(SimpleXMLElement $item): WxrItem
    {
        $wp = $item->children('wp', true);

        $postIdRaw = trim((string) $wp->post_id);
        $slug = trim((string) $wp->post_name);
        $publishedAt = $this->normalizeDate(trim((string) $wp->post_date));

        $categorySlugs = [];
        $tagSlugs = [];
        foreach ($item->category as $category) {
            $domain = (string) ($category['domain'] ?? '');
            $nicename = trim((string) ($category['nicename'] ?? ''));
            $value = $nicename !== '' ? $nicename : trim((string) $category);
            if ($value === '') {
                continue;
            }
            if ($domain === 'post_tag') {
                $tagSlugs[] = $value;
            } elseif ($domain === 'category') {
                $categorySlugs[] = $value;
            }
        }

        return new WxrItem(
            wpPostId: $postIdRaw === '' ? null : (int) $postIdRaw,
            postType: trim((string) $wp->post_type),
            status: trim((string) $wp->status),
            title: trim((string) $item->title),
            slug: $slug === '' ? null : $slug,
            contentHtml: (string) $item->children('content', true)->encoded,
            excerptHtml: (string) $item->children('excerpt', true)->encoded,
            publishedAtIso: $publishedAt,
            originalLink: trim((string) $item->link) ?: null,
            categorySlugs: array_values(array_unique($categorySlugs)),
            tagSlugs: array_values(array_unique($tagSlugs)),
        );
    }

    /** WP `post_date` is `Y-m-d H:i:s` (site-local); normalize to ISO-8601, else null. */
    private function normalizeDate(string $raw): ?string
    {
        if ($raw === '' || $raw === '0000-00-00 00:00:00') {
            return null;
        }

        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $raw);

        return $parsed === false ? null : $parsed->format('Y-m-d\TH:i:sP');
    }
}
