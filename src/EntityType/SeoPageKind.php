<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

/**
 * How a type's records present themselves to crawlers and unfurlers (#1020).
 *
 * Before this existed, every record that was not the pinned front page was typed
 * `og:type=article` + JSON-LD `BlogPosting` — a contact page, a company profile and
 * a blog post all claimed to be dated articles. `BlogPosting` also drags
 * `datePublished` / `dateModified` along, so a page with no publication date
 * advertised one anyway.
 *
 * Chosen per entity type rather than per record: the distinction ("is this type a
 * catalogue of pages, or a stream of dated posts?") is a property of the type, and
 * production has 17 types in total, so setting them explicitly is cheap.
 *
 * The default for a *new* type is {@see WebPage}, deliberately. The two mistakes are
 * not symmetric: typing an article as a page loses a little richness, while typing a
 * page as an article states a publication date that does not exist — a factual claim
 * to search engines that is simply untrue.
 */
enum SeoPageKind: string
{
    /** A standing page: `og:type=website`, JSON-LD `WebPage`, no publication dates. */
    case WebPage = 'webpage';

    /** A dated entry: `og:type=article`, JSON-LD `BlogPosting`, dates included. */
    case Article = 'article';

    public static function fromStorage(mixed $value): self
    {
        return is_string($value) ? (self::tryFrom($value) ?? self::WebPage) : self::WebPage;
    }

    /** The `og:type` value for this kind. */
    public function ogType(): string
    {
        return $this === self::Article ? 'article' : 'website';
    }
}
