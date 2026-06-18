<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Theme;

use NeNeRecords\Media\Media;
use NeNeRecords\Tests\Media\InMemoryMediaRepository;
use NeNeRecords\Theme\ThemeThumbnailResolver;
use PHPUnit\Framework\TestCase;

final class ThemeThumbnailResolverTest extends TestCase
{
    private function media(int $id, string $url): Media
    {
        return new Media(
            id: $id,
            originalName: 'preview.webp',
            storedName: 'preview.webp',
            mimeType: 'image/webp',
            size: 1234,
            url: $url,
            createdAt: '2026-06-18 00:00:00',
        );
    }

    public function testResolvesMediaIdToUrl(): void
    {
        $repo = new InMemoryMediaRepository([$this->media(7, '/media/preview-7.webp')]);
        $resolver = new ThemeThumbnailResolver($repo);

        self::assertSame(
            '/media/preview-7.webp',
            $resolver->resolve(['assets' => ['preview' => 7]]),
        );
    }

    public function testResolvesPerModeLightMediaId(): void
    {
        $repo = new InMemoryMediaRepository([$this->media(3, '/media/light.webp')]);
        $resolver = new ThemeThumbnailResolver($repo);

        self::assertSame(
            '/media/light.webp',
            $resolver->resolve(['assets' => ['preview' => ['light' => 3, 'dark' => 9]]]),
        );
    }

    public function testReturnsEmptyForMissingMediaOrNoPreview(): void
    {
        $resolver = new ThemeThumbnailResolver(new InMemoryMediaRepository());

        self::assertSame('', $resolver->resolve([]));
        self::assertSame('', $resolver->resolve(['assets' => []]));
        self::assertSame('', $resolver->resolve(['assets' => ['preview' => 99]]));
        // Bundle path (string) is not a media id → empty (runtime can't host files).
        self::assertSame('', $resolver->resolve(['assets' => ['preview' => 'thumbs/x.webp']]));
    }
}
