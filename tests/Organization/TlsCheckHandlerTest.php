<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Organization;

use NeNeRecords\Organization\Organization;
use NeNeRecords\Organization\TlsCheckHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

final class TlsCheckHandlerTest extends TestCase
{
    private function handler(): TlsCheckHandler
    {
        $repo = new InMemoryOrganizationRepository();
        $repo->save(new Organization('Shop', 'shop', 'free', true));
        $repo->save(new Organization('Gone', 'gone', 'free', false)); // inactive
        $repo->save(new Organization('Custom', 'custom', 'free', true, customDomain: 'mycustom.example'));

        return new TlsCheckHandler($repo, new Psr17Factory(), 'nene-records.com');
    }

    private function statusFor(string $domain): int
    {
        $request = (new Psr17Factory())
            ->createServerRequest('GET', 'http://records-app/internal/tls-check')
            ->withQueryParams(['domain' => $domain]);

        return $this->handler()->handle($request)->getStatusCode();
    }

    public function testApexIsAllowed(): void
    {
        self::assertSame(200, $this->statusFor('nene-records.com'));
    }

    public function testActiveTenantSubdomainIsAllowed(): void
    {
        self::assertSame(200, $this->statusFor('shop.nene-records.com'));
        self::assertSame(200, $this->statusFor('shop.nene-records.com:443'));
    }

    public function testUnknownSubdomainIsDenied(): void
    {
        self::assertSame(403, $this->statusFor('nope.nene-records.com'));
    }

    public function testInactiveTenantIsDenied(): void
    {
        self::assertSame(403, $this->statusFor('gone.nene-records.com'));
    }

    public function testRegisteredCustomDomainIsAllowed(): void
    {
        self::assertSame(200, $this->statusFor('mycustom.example'));
    }

    public function testUnregisteredHostIsDenied(): void
    {
        self::assertSame(403, $this->statusFor('evil.example.com'));
    }

    public function testEmptyDomainIsBadRequest(): void
    {
        self::assertSame(400, $this->statusFor(''));
    }
}
