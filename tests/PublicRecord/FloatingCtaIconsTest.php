<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PublicRecord;

use NeNeRecords\PublicRecord\FloatingCtaIcons;
use PHPUnit\Framework\TestCase;

final class FloatingCtaIconsTest extends TestCase
{
    public function testKeysAndHaveStayInSync(): void
    {
        $keys = FloatingCtaIcons::keys();
        self::assertContains('calendar', $keys);
        self::assertContains('video', $keys);
        foreach ($keys as $id) {
            self::assertTrue(FloatingCtaIcons::has($id));
        }
        self::assertFalse(FloatingCtaIcons::has('nope'));
        self::assertFalse(FloatingCtaIcons::has(''));
    }

    public function testSvgIsUniformAndScriptFree(): void
    {
        foreach (FloatingCtaIcons::keys() as $id) {
            $svg = FloatingCtaIcons::svg($id);
            self::assertStringStartsWith('<svg width="18" height="18" viewBox="0 0 24 24"', $svg);
            self::assertStringContainsString('stroke="currentColor"', $svg);
            self::assertStringContainsString('fill="none"', $svg);
            self::assertStringContainsString('aria-hidden="true"', $svg);
            self::assertStringEndsWith('</svg>', $svg);
            // Curated + safe: no script-bearing constructs.
            self::assertStringNotContainsStringIgnoringCase('<script', $svg);
            self::assertStringNotContainsStringIgnoringCase('onload', $svg);
            self::assertStringNotContainsString('foreignObject', $svg);
            self::assertLessThanOrEqual(600, strlen($svg));
        }
    }

    public function testUnknownIdRendersEmpty(): void
    {
        self::assertSame('', FloatingCtaIcons::svg('nope'));
    }
}
