<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PublicRecord;

use DateTimeImmutable;
use NeNeRecords\PublicRecord\PublicPermalinkResolver;
use PHPUnit\Framework\TestCase;

final class PublicPermalinkResolverTest extends TestCase
{
    public function testNullPatternFallsBackToDefaultTypeId(): void
    {
        self::assertSame(
            '/posts/42',
            PublicPermalinkResolver::resolve(null, 'posts', 'my-article', 42, null),
        );
    }

    public function testPostNamePatternUsesSlug(): void
    {
        self::assertSame(
            '/posts/my-article',
            PublicPermalinkResolver::resolve('/{type}/{slug}', 'posts', 'my-article', 42, null),
        );
    }

    public function testSlugFallsBackToIdWhenMissing(): void
    {
        self::assertSame(
            '/posts/42',
            PublicPermalinkResolver::resolve('/{type}/{slug}', 'posts', null, 42, null),
        );
    }

    public function testDatePatternUsesUtcPublishedAt(): void
    {
        self::assertSame(
            '/posts/2026/01/15/my-article',
            PublicPermalinkResolver::resolve(
                '/{type}/{year}/{month}/{day}/{slug}',
                'posts',
                'my-article',
                42,
                new DateTimeImmutable('2026-01-15T10:00:00+09:00'),
            ),
        );
    }

    public function testDateTokensAreZeroedWithoutPublishedAt(): void
    {
        self::assertSame(
            '/posts/0000/00/00/my-article',
            PublicPermalinkResolver::resolve(
                '/{type}/{year}/{month}/{day}/{slug}',
                'posts',
                'my-article',
                42,
                null,
            ),
        );
    }
}
