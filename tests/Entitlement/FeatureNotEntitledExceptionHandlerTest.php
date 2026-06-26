<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Entitlement;

use Nene2\Error\ProblemDetailsResponseFactory;
use NeNeRecords\Entitlement\FeatureNotEntitledException;
use NeNeRecords\Entitlement\FeatureNotEntitledExceptionHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class FeatureNotEntitledExceptionHandlerTest extends TestCase
{
    private FeatureNotEntitledExceptionHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $factory = new Psr17Factory();
        $this->handler = new FeatureNotEntitledExceptionHandler(
            new ProblemDetailsResponseFactory($factory, $factory),
        );
    }

    public function testSupportsOnlyFeatureNotEntitled(): void
    {
        self::assertTrue($this->handler->supports(new FeatureNotEntitledException('custom domain')));
        self::assertFalse($this->handler->supports(new RuntimeException('other')));
    }

    public function testMapsTo402(): void
    {
        $factory = new Psr17Factory();
        $request = $factory->createServerRequest('PATCH', 'https://example.test/api/v1/superadmin/organizations/1');

        $response = $this->handler->handle(new FeatureNotEntitledException('custom domain'), $request);

        self::assertSame(402, $response->getStatusCode());
    }
}
