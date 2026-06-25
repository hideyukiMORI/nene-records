<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Organization\Resolution;

use NeNeRecords\Organization\Resolution\PathPrefixResolutionStrategy;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

final class PathPrefixResolutionStrategyTest extends TestCase
{
    private function basePrefixFor(string $path): string
    {
        $factory = new Psr17Factory();

        return (new PathPrefixResolutionStrategy())->basePrefix(
            $factory->createServerRequest('GET', 'https://example.test' . $path),
        );
    }

    public function testBasePrefixIsTheLeadingSegment(): void
    {
        self::assertSame('/myshop', $this->basePrefixFor('/myshop/posts/1'));
        self::assertSame('/myshop', $this->basePrefixFor('/myshop'));
    }

    public function testBasePrefixEmptyForBypassPaths(): void
    {
        // Global routes (auth / health / org management) carry no tenant prefix.
        self::assertSame('', $this->basePrefixFor('/health'));
        self::assertSame('', $this->basePrefixFor('/api/v1/auth/login'));
        self::assertSame('', $this->basePrefixFor('/'));
    }
}
