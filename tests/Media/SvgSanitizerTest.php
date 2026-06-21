<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Media;

use NeNeRecords\Media\MediaInvalidTypeException;
use NeNeRecords\Media\SvgSanitizer;
use PHPUnit\Framework\TestCase;

final class SvgSanitizerTest extends TestCase
{
    private SvgSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new SvgSanitizer();
    }

    /** @param non-empty-string $inner */
    private function svg(string $inner, string $extraRootAttrs = ''): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"' . $extraRootAttrs . '>' . $inner . '</svg>';
    }

    public function testRemovesScriptElement(): void
    {
        $out = $this->sanitizer->sanitize($this->svg('<script>alert(1)</script><rect width="1" height="1"/>'));

        self::assertStringNotContainsStringIgnoringCase('script', $out);
        self::assertStringNotContainsString('alert(1)', $out);
        self::assertStringContainsString('<rect', $out);
    }

    public function testRemovesScriptElementWithCdata(): void
    {
        $out = $this->sanitizer->sanitize($this->svg('<script><![CDATA[alert(document.cookie)]]></script><g/>'));

        self::assertStringNotContainsString('alert', $out);
        self::assertStringNotContainsStringIgnoringCase('cdata', $out);
    }

    public function testRemovesCaseVariantScriptElement(): void
    {
        // XML is case-sensitive, but we lower-case local names so <ScRiPt> is still dropped.
        $out = $this->sanitizer->sanitize($this->svg('<ScRiPt>alert(1)</ScRiPt><circle r="1"/>'));

        self::assertStringNotContainsStringIgnoringCase('alert', $out);
        self::assertStringContainsString('<circle', $out);
    }

    public function testRemovesEventHandlerAttributes(): void
    {
        $out = $this->sanitizer->sanitize($this->svg('<rect width="1" height="1" onload="x()" onClick="y()"/>'));

        self::assertStringNotContainsStringIgnoringCase('onload', $out);
        self::assertStringNotContainsStringIgnoringCase('onclick', $out);
        self::assertStringContainsString('<rect', $out);
    }

    public function testStripsJavascriptHref(): void
    {
        $out = $this->sanitizer->sanitize($this->svg('<a xlink:href="javascript:alert(1)"><text>hi</text></a>'));

        self::assertStringNotContainsStringIgnoringCase('javascript:', $out);
        self::assertStringContainsString('hi', $out);
    }

    public function testStripsEntityEncodedJavascriptHref(): void
    {
        // &#106; = 'j' — DOM decodes char refs, so the scheme must still be caught.
        $out = $this->sanitizer->sanitize($this->svg('<a href="&#106;avascript:alert(1)"><text>x</text></a>'));

        self::assertStringNotContainsStringIgnoringCase('javascript:', $out);
        self::assertStringNotContainsString('alert(1)', $out);
    }

    public function testRemovesForeignObject(): void
    {
        $out = $this->sanitizer->sanitize($this->svg('<foreignObject><body xmlns="http://www.w3.org/1999/xhtml"><script>alert(1)</script></body></foreignObject><rect/>'));

        self::assertStringNotContainsStringIgnoringCase('foreignobject', $out);
        self::assertStringNotContainsString('alert(1)', $out);
        self::assertStringContainsString('<rect', $out);
    }

    public function testDropsExternalUseReferenceButKeepsInternalFragment(): void
    {
        $external = $this->sanitizer->sanitize($this->svg('<use xlink:href="https://evil.example/x.svg#a"/>'));
        self::assertStringNotContainsString('evil.example', $external);

        $internal = $this->sanitizer->sanitize($this->svg('<defs><rect id="a" width="1" height="1"/></defs><use xlink:href="#a"/>'));
        self::assertStringContainsString('#a', $internal);
    }

    public function testDropsDataUriImageHref(): void
    {
        // Valid XML attribute value, but a data: URI href must be dropped (it can
        // nest an SVG/HTML payload). Internal #id and real media URLs are the only
        // intended references.
        $out = $this->sanitizer->sanitize($this->svg('<image href="data:image/svg+xml;base64,PHN2Zz48c2NyaXB0Lz48L3N2Zz4="/>'));

        self::assertStringNotContainsString('data:', $out);
        self::assertStringNotContainsString('base64', $out);
    }

    public function testStripsDangerousStyleAttributeButKeepsInternalFilterRef(): void
    {
        $dangerous = $this->sanitizer->sanitize($this->svg('<rect style="background:url(https://evil.example/x)" width="1" height="1"/>'));
        self::assertStringNotContainsString('evil.example', $dangerous);

        // url(#id) for an in-document filter is legitimate and must survive.
        $safe = $this->sanitizer->sanitize($this->svg('<rect style="filter:url(#blur)" width="1" height="1"/>'));
        self::assertStringContainsString('url(#blur)', $safe);
    }

    public function testRemovesSmilAnimationTargetingHref(): void
    {
        $out = $this->sanitizer->sanitize($this->svg('<a><set attributeName="xlink:href" to="javascript:alert(1)"/><text>x</text></a>'));

        self::assertStringNotContainsStringIgnoringCase('javascript:', $out);
        self::assertStringNotContainsStringIgnoringCase('<set', $out);
    }

    public function testStripsJavascriptInAnimateValues(): void
    {
        $out = $this->sanitizer->sanitize($this->svg('<rect width="1" height="1"><animate attributeName="fill" to="javascript:alert(1)"/></rect>'));

        self::assertStringNotContainsStringIgnoringCase('javascript:', $out);
    }

    public function testRejectsDoctypeToBlockXxe(): void
    {
        $this->expectException(MediaInvalidTypeException::class);
        $this->sanitizer->sanitize(
            '<?xml version="1.0"?><!DOCTYPE svg [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>'
            . '<svg xmlns="http://www.w3.org/2000/svg"><text>&xxe;</text></svg>',
        );
    }

    public function testRejectsNonSvgRoot(): void
    {
        $this->expectException(MediaInvalidTypeException::class);
        $this->sanitizer->sanitize('<html><body>nope</body></html>');
    }

    public function testRejectsUnparseableInput(): void
    {
        $this->expectException(MediaInvalidTypeException::class);
        $this->sanitizer->sanitize('this is not xml at all <<<');
    }

    public function testKeepsBenignSvgContent(): void
    {
        $out = $this->sanitizer->sanitize($this->svg(
            '<title>Logo</title><g fill="#123456"><path d="M0 0 L10 10"/><circle cx="5" cy="5" r="3"/></g>',
        ));

        self::assertStringContainsString('<path', $out);
        self::assertStringContainsString('<circle', $out);
        self::assertStringContainsString('#123456', $out);
        self::assertStringContainsString('Logo', $out);
    }

    public function testLooksLikeSvgDetectsSpoofedContent(): void
    {
        self::assertTrue(SvgSanitizer::looksLikeSvg('<svg xmlns="http://www.w3.org/2000/svg"></svg>'));
        self::assertTrue(SvgSanitizer::looksLikeSvg("\xEF\xBB\xBF<?xml version=\"1.0\"?>\n<svg></svg>"));
        self::assertTrue(SvgSanitizer::looksLikeSvg("<!-- a comment -->\n<svg/>"));
        self::assertFalse(SvgSanitizer::looksLikeSvg('a plain text note mentioning <svg> midway'));
        self::assertFalse(SvgSanitizer::looksLikeSvg("\x89PNG\r\n\x1a\n binary"));
    }
}
