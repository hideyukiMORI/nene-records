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

    // ── Organization scope checks ────────────────────────────────────────────────

    /**
     * admin の JWT org_id が解決済み org_id と一致する → 通過
     */
    public function testOrgScopedRouteWithMatchingOrgIdPassesThrough(): void
    {
        $request = $this->factory
            ->createServerRequest('GET', 'https://example.test/api/v1/users')
            ->withAttribute('nene2.auth.claims', ['role' => 'admin', 'sub' => 'admin@example.com', 'org_id' => 1])
            ->withAttribute('nene2.org.id', 1);

        $response = $this->middleware->process($request, $this->createPassThroughHandler());

        self::assertSame(204, $response->getStatusCode());
    }

    /**
     * admin の JWT org_id が解決済み org_id と異なる → 403
     */
    public function testOrgScopedRouteWithMismatchedOrgIdReturns403(): void
    {
        $request = $this->factory
            ->createServerRequest('GET', 'https://example.test/api/v1/users')
            ->withAttribute('nene2.auth.claims', ['role' => 'admin', 'sub' => 'admin@example.com', 'org_id' => 1])
            ->withAttribute('nene2.org.id', 2);

        $response = $this->middleware->process($request, $this->createPassThroughHandler());

        self::assertSame(403, $response->getStatusCode());
    }

    /**
     * superadmin は org スコープチェックをバイパスする
     */
    public function testSuperadminBypassesOrgScopeCheckOnOrgScopedRoute(): void
    {
        $request = $this->factory
            ->createServerRequest('GET', 'https://example.test/api/v1/users')
            ->withAttribute('nene2.auth.claims', ['role' => 'superadmin', 'sub' => 'sa@example.com', 'org_id' => null])
            ->withAttribute('nene2.org.id', 99);

        $response = $this->middleware->process($request, $this->createPassThroughHandler());

        self::assertSame(204, $response->getStatusCode());
    }

    /**
     * nene2.org.id 属性がないルート（org 非スコープ）はチェックをスキップ
     */
    public function testRouteWithoutOrgAttributeSkipsOrgScopeCheck(): void
    {
        $request = $this->factory
            ->createServerRequest('GET', 'https://example.test/api/v1/users')
            ->withAttribute('nene2.auth.claims', ['role' => 'admin', 'sub' => 'admin@example.com', 'org_id' => 1]);
        // nene2.org.id 属性を設定しない

        $response = $this->middleware->process($request, $this->createPassThroughHandler());

        self::assertSame(204, $response->getStatusCode());
    }

    /**
     * nene2.org.id が文字列（非 int）の場合はチェックをスキップ
     */
    public function testRouteWithStringOrgIdAttributeSkipsOrgScopeCheck(): void
    {
        $request = $this->factory
            ->createServerRequest('GET', 'https://example.test/api/v1/users')
            ->withAttribute('nene2.auth.claims', ['role' => 'admin', 'sub' => 'admin@example.com', 'org_id' => 1])
            ->withAttribute('nene2.org.id', '1'); // 文字列は is_int() で false

        $response = $this->middleware->process($request, $this->createPassThroughHandler());

        self::assertSame(204, $response->getStatusCode());
    }

    /**
     * JWT に org_id がない admin が org スコープルートにアクセス → 403
     */
    public function testAdminWithoutJwtOrgIdOnOrgScopedRouteReturns403(): void
    {
        $request = $this->factory
            ->createServerRequest('GET', 'https://example.test/api/v1/users')
            ->withAttribute('nene2.auth.claims', ['role' => 'admin', 'sub' => 'admin@example.com'])
            // org_id クレームなし
            ->withAttribute('nene2.org.id', 1);

        $response = $this->middleware->process($request, $this->createPassThroughHandler());

        self::assertSame(403, $response->getStatusCode());
    }

    /**
     * editor の JWT org_id が解決済み org_id と一致する → 通過
     */
    public function testEditorWithMatchingOrgIdPassesThrough(): void
    {
        $request = $this->factory
            ->createServerRequest('POST', 'https://example.test/api/v1/entities')
            ->withAttribute('nene2.auth.claims', ['role' => 'editor', 'sub' => 'editor@example.com', 'org_id' => 5])
            ->withAttribute('nene2.org.id', 5);

        $response = $this->middleware->process($request, $this->createPassThroughHandler());

        self::assertSame(204, $response->getStatusCode());
    }

    /**
     * editor の JWT org_id が解決済み org_id と異なる → 403
     */
    public function testEditorWithMismatchedOrgIdReturns403(): void
    {
        $request = $this->factory
            ->createServerRequest('POST', 'https://example.test/api/v1/entities')
            ->withAttribute('nene2.auth.claims', ['role' => 'editor', 'sub' => 'editor@example.com', 'org_id' => 3])
            ->withAttribute('nene2.org.id', 7);

        $response = $this->middleware->process($request, $this->createPassThroughHandler());

        self::assertSame(403, $response->getStatusCode());
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
