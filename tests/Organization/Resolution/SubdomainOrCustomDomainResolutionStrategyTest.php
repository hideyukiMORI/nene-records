<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Organization\Resolution;

use NeNeRecords\Organization\Resolution\SubdomainOrCustomDomainResolutionStrategy;
use NeNeRecords\Organization\Resolution\SubdomainResolutionStrategy;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

final class SubdomainOrCustomDomainResolutionStrategyTest extends TestCase
{
    private Psr17Factory $factory;
    private SubdomainOrCustomDomainResolutionStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new Psr17Factory();
        $this->strategy = new SubdomainOrCustomDomainResolutionStrategy(
            new SubdomainResolutionStrategy('nene-records.com'),
        );
    }

    public function testBaseDomainSubdomainResolvesToOrgSlug(): void
    {
        self::assertSame('org1', $this->strategy->resolve($this->request('https://org1.nene-records.com/')));
    }

    public function testApexResolvesToNull(): void
    {
        self::assertNull($this->strategy->resolve($this->request('https://nene-records.com/')));
    }

    public function testCustomSubdomainReturnsFullHost(): void
    {
        // Not a base-domain subdomain → hand the full host to findByCustomDomain().
        self::assertSame('blog.example.com', $this->strategy->resolve($this->request('https://blog.example.com/')));
    }

    public function testCustomApexDomainReturnsFullHost(): void
    {
        self::assertSame('example.com', $this->strategy->resolve($this->request('https://example.com/')));
    }

    public function testIsApexOnlyForBaseDomain(): void
    {
        self::assertTrue($this->strategy->isApex($this->request('https://nene-records.com/')));
        self::assertFalse($this->strategy->isApex($this->request('https://org1.nene-records.com/')));
        self::assertFalse($this->strategy->isApex($this->request('https://blog.example.com/')));
    }

    private function request(string $url): \Psr\Http\Message\ServerRequestInterface
    {
        return $this->factory->createServerRequest('GET', $url);
    }
}
