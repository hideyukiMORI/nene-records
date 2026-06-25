<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Http;

use NeNeRecords\Http\BasePath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BasePathTest extends TestCase
{
    /** @return iterable<string, array{?string, string}> */
    public static function normalizeCases(): iterable
    {
        yield 'null is root' => [null, ''];
        yield 'empty is root' => ['', ''];
        yield 'slash is root' => ['/', ''];
        yield 'leading slash kept' => ['/blog', '/blog'];
        yield 'bare segment gets slash' => ['blog', '/blog'];
        yield 'trailing slash stripped' => ['/blog/', '/blog'];
        yield 'both slashes' => ['blog/', '/blog'];
        yield 'nested' => ['/a/b', '/a/b'];
        yield 'whitespace trimmed' => ['  /blog  ', '/blog'];
    }

    #[DataProvider('normalizeCases')]
    public function testNormalize(?string $raw, string $expected): void
    {
        self::assertSame($expected, BasePath::normalize($raw));
    }

    public function testPrefixIsNoOpAtRoot(): void
    {
        self::assertSame('/posts/1', BasePath::prefix('', '/posts/1'));
        self::assertSame('/', BasePath::prefix('', '/'));
    }

    public function testPrefixUnderSubdirectory(): void
    {
        self::assertSame('/blog/posts/1', BasePath::prefix('/blog', '/posts/1'));
        self::assertSame('/blog/assets/x.js', BasePath::prefix('/blog', '/assets/x.js'));
        self::assertSame('/blog/', BasePath::prefix('/blog', '/')); // site root
    }

    public function testFromEnvReadsAndNormalizes(): void
    {
        putenv('APP_BASE_PATH=/blog/');
        try {
            self::assertSame('/blog', BasePath::fromEnv());
        } finally {
            putenv('APP_BASE_PATH');
        }

        self::assertSame('', BasePath::fromEnv()); // unset → root
    }
}
