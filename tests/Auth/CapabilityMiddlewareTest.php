<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Auth;

use Nene2\Error\ProblemDetailsResponseFactory;
use NeNeRecords\Auth\CapabilityMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CapabilityMiddlewareTest extends TestCase
{
    private Psr17Factory $factory;

    private CapabilityMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();
        $this->middleware = new CapabilityMiddleware(
            new ProblemDetailsResponseFactory($this->factory, $this->factory),
        );
    }

    public function testUnauthenticatedRequestPassesThrough(): void
    {
        $request = $this->factory->createServerRequest('DELETE', 'https://example.test/api/v1/entity-types/1');

        $response = $this->middleware->process($request, $this->createPassThroughHandler());

        self::assertSame(204, $response->getStatusCode());
    }

    public function testEditorDeletingEntityTypeReturns403(): void
    {
        $request = $this->factory
            ->createServerRequest('DELETE', 'https://example.test/api/v1/entity-types/1')
            ->withAttribute('nene2.auth.claims', ['role' => 'editor', 'sub' => 'editor@example.com']);

        $response = $this->middleware->process($request, $this->createPassThroughHandler());

        self::assertSame(403, $response->getStatusCode());
    }

    public function testAdminDeletingEntityTypePassesThrough(): void
    {
        $request = $this->factory
            ->createServerRequest('DELETE', 'https://example.test/api/v1/entity-types/1')
            ->withAttribute('nene2.auth.claims', ['role' => 'admin', 'sub' => 'admin@example.com']);

        $response = $this->middleware->process($request, $this->createPassThroughHandler());

        self::assertSame(204, $response->getStatusCode());
    }

    public function testEditorCreatingEntityPassesThrough(): void
    {
        $request = $this->factory
            ->createServerRequest('POST', 'https://example.test/api/v1/entities')
            ->withAttribute('nene2.auth.claims', ['role' => 'editor', 'sub' => 'editor@example.com']);

        $response = $this->middleware->process($request, $this->createPassThroughHandler());

        self::assertSame(204, $response->getStatusCode());
    }

    public function testEditorUpdatingSettingsReturns403(): void
    {
        $request = $this->factory
            ->createServerRequest('PUT', 'https://example.test/api/v1/settings/site_name')
            ->withAttribute('nene2.auth.claims', ['role' => 'editor', 'sub' => 'editor@example.com']);

        $response = $this->middleware->process($request, $this->createPassThroughHandler());

        self::assertSame(403, $response->getStatusCode());
    }

    public function testEditorReadingSettingsPassesThrough(): void
    {
        $request = $this->factory
            ->createServerRequest('GET', 'https://example.test/api/v1/settings')
            ->withAttribute('nene2.auth.claims', ['role' => 'editor', 'sub' => 'editor@example.com']);

        $response = $this->middleware->process($request, $this->createPassThroughHandler());

        self::assertSame(204, $response->getStatusCode());
    }

    private function createPassThroughHandler(): RequestHandlerInterface
    {
        return new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): Response
            {
                return new Response(204);
            }
        };
    }
}
