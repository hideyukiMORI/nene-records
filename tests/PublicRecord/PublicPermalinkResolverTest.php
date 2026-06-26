<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PublicRecord;

use DateTimeImmutable;
use NeNeRecords\PublicRecord\PublicPermalinkResolver;
use PHPUnit\Framework\TestCase;

final class PublicPermalinkResolverTest extends TestCase
{
    public function testCanonicalPathUsesCustomPermalinkVerbatimWhenSet(): void
    {
        // A non-empty custom permalink overrides the type pattern entirely (#651).
        self::assertSame(
            '/company/about/team',
            PublicPermalinkResolver::canonicalPath('/company/about/team', '/{type}/{slug}', 'posts', 'my-article', 42, null),
        );
    }

    public function testCanonicalPathFallsBackToPatternWhenPermalinkAbsent(): void
    {
        self::assertSame(
            '/posts/my-article',
            PublicPermalinkResolver::canonicalPath(null, '/{type}/{slug}', 'posts', 'my-article', 42, null),
        );
        // Empty string is treated as "no custom permalink" → default pattern.
        self::assertSame(
            '/posts/42',
            PublicPermalinkResolver::canonicalPath('', null, 'posts', null, 42, null),
        );
    }

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

    public function testExtractEntityKeyResolvesIdForDefaultPattern(): void
    {
        self::assertSame(
            ['entityId' => 42, 'entitySlug' => null],
            PublicPermalinkResolver::extractEntityKey('/{type}/{id}', '42'),
        );
        // null pattern falls back to the default (id) pattern.
        self::assertSame(
            ['entityId' => 42, 'entitySlug' => null],
            PublicPermalinkResolver::extractEntityKey(null, '42'),
        );
    }

    public function testExtractEntityKeyResolvesSlugForSlugPatterns(): void
    {
        self::assertSame(
            ['entityId' => null, 'entitySlug' => 'my-article'],
            PublicPermalinkResolver::extractEntityKey('/{type}/{slug}', 'my-article'),
        );
        // Date pattern → last segment is the slug.
        self::assertSame(
            ['entityId' => null, 'entitySlug' => 'my-article'],
            PublicPermalinkResolver::extractEntityKey('/{type}/{year}/{month}/{slug}', '2026/06/my-article'),
        );
    }

    public function testExtractEntityKeyFallsBackToSlugForNonNumericIdPattern(): void
    {
        // Pattern expects an id but the segment isn't numeric → treat as slug.
        self::assertSame(
            ['entityId' => null, 'entitySlug' => 'not-a-number'],
            PublicPermalinkResolver::extractEntityKey('/{type}/{id}', 'not-a-number'),
        );
    }
}
