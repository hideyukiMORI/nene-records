<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Server-side HTML sanitizer for the crawlable SSR of `html`-typed fields.
 *
 * Mirrors the front-end {@see SanitizedHtml} (DOMPurify) policy so the SSR twin
 * and the SPA render the same trusted markup: rich, styled content is kept while
 * scripts, `on*` handlers and `javascript:` URLs are stripped — upholding the
 * "no arbitrary JS" guarantee for imported (e.g. WordPress) content.
 */
final readonly class PublicHtmlSanitizer
{
    private HtmlSanitizer $sanitizer;

    public function __construct()
    {
        $config = (new HtmlSanitizerConfig())
            ->allowSafeElements()
            ->allowElement('img', ['src', 'alt', 'title', 'width', 'height'])
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->allowAttribute('style', '*');

        $this->sanitizer = new HtmlSanitizer($config);
    }

    public function sanitize(string $html): string
    {
        return $this->sanitizer->sanitize($html);
    }
}
