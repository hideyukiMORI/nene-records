<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PublicRecord;

use NeNeRecords\PublicRecord\PublicHtmlSanitizer;
use PHPUnit\Framework\TestCase;

final class PublicHtmlSanitizerTest extends TestCase
{
    private PublicHtmlSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new PublicHtmlSanitizer();
    }

    public function testKeepsRichContentMarkup(): void
    {
        $html = '<p>Hello <strong>world</strong> and <a href="/posts/1">link</a>.</p>';
        $clean = $this->sanitizer->sanitize($html);

        self::assertStringContainsString('<strong>world</strong>', $clean);
        self::assertStringContainsString('<a href="/posts/1">link</a>', $clean);
    }

    public function testKeepsImagesIncludingRelativeMediaUrls(): void
    {
        $clean = $this->sanitizer->sanitize('<img src="/media/imported/image.jpg" alt="pic" />');

        self::assertStringContainsString('<img', $clean);
        self::assertStringContainsString('/media/imported/image.jpg', $clean);
    }

    public function testStripsScriptTags(): void
    {
        $clean = $this->sanitizer->sanitize('<p>ok</p><script>alert(1)</script>');

        self::assertStringContainsString('ok', $clean);
        self::assertStringNotContainsString('<script', $clean);
        self::assertStringNotContainsString('alert(1)', $clean);
    }

    public function testStripsEventHandlersAndJavascriptUrls(): void
    {
        $clean = $this->sanitizer->sanitize('<img src="x" onerror="alert(1)"><a href="javascript:alert(1)">x</a>');

        self::assertStringNotContainsString('onerror', $clean);
        self::assertStringNotContainsString('javascript:', $clean);
    }

    public function testStripsStyleBlocksButKeepsInlineStyle(): void
    {
        $clean = $this->sanitizer->sanitize('<style>body{display:none}</style><p style="color:red">x</p>');

        self::assertStringNotContainsString('<style', $clean);
        self::assertStringContainsString('style="color:red"', $clean);
    }

    public function testKeepsLongCustomPageBodyPastSymfonyDefaultLimit(): void
    {
        // A full `bare`/custom page body easily exceeds Symfony's 20 KB default
        // maxInputLength; raising the cap keeps the whole body crawlable in the
        // SSR instead of silently truncating it mid-page.
        $filler = str_repeat('<p style="margin:0">段落テキスト。</p>', 4000);
        $html = $filler . '<footer id="page-end">末尾</footer>';
        self::assertGreaterThan(20_000, \strlen($html));

        $clean = $this->sanitizer->sanitize($html);

        self::assertStringContainsString('id="page-end"', $clean);
        self::assertStringContainsString('末尾', $clean);
    }
}
