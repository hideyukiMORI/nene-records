<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Media;

use NeNeRecords\Media\MediaDerivativeUrl;
use PHPUnit\Framework\TestCase;

final class MediaDerivativeUrlTest extends TestCase
{
    public function testResolvesOgDerivativeForRelativeMediaImage(): void
    {
        self::assertSame(
            '/media/og/2026/06/hero.png',
            MediaDerivativeUrl::forPreset('/media/2026/06/hero.png', 'og'),
        );
    }

    public function testPreservesAbsolutePrefix(): void
    {
        self::assertSame(
            'https://cdn.example.test/media/og/2026/06/hero.jpg',
            MediaDerivativeUrl::forPreset('https://cdn.example.test/media/2026/06/hero.jpg', 'og'),
        );
    }

    public function testReturnsNullForUnknownPreset(): void
    {
        self::assertNull(MediaDerivativeUrl::forPreset('/media/2026/06/hero.png', 'bogus'));
    }

    public function testReturnsNullForNonImageExtension(): void
    {
        self::assertNull(MediaDerivativeUrl::forPreset('/media/2026/06/doc.pdf', 'og'));
    }

    public function testReturnsNullForNonMediaUrl(): void
    {
        self::assertNull(MediaDerivativeUrl::forPreset('https://example.test/posts/42', 'og'));
        self::assertNull(MediaDerivativeUrl::forPreset('', 'og'));
    }
}
