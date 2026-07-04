<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Http;

use DateTimeImmutable;
use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\EntityType\EntityType;
use NeNeRecords\Http\CustomPermalinkResolver;
use NeNeRecords\Http\PublicPermalinkRendererInterface;
use NeNeRecords\Http\SingleOriginKernel;
use NeNeRecords\Http\SpaShellFallback;
use NeNeRecords\PublicRecord\FrontPageSetting;
use NeNeRecords\PublicRecord\PublicRecordViewRendererInterface;
use NeNeRecords\PublicRecord\RenderPublicHomeHandler;
use NeNeRecords\Setting\SettingDef;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\EntityType\InMemoryEntityTypeRepository;
use NeNeRecords\Tests\Setting\InMemorySettingRepository;
use NeNeRecords\Tests\UrlRedirect\InMemoryUrlRedirectRepository;
use NeNeRecords\UrlRedirect\UrlRedirectResolver;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class SingleOriginKernelTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryUrlRedirectRepository $redirects;
    private string $shellPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new Psr17Factory();
        $this->redirects = new InMemoryUrlRedirectRepository();

        $shellPath = tempnam(sys_get_temp_dir(), 'shell_');
        self::assertNotFalse($shellPath);
        file_put_contents($shellPath, '<!doctype html><div id="root"></div>');
        $this->shellPath = $shellPath;
    }

    protected function tearDown(): void
    {
        @unlink($this->shellPath);
        parent::tearDown();
    }

    /** Inner application handler that always returns the given status. */
    private function appReturning(int $status): RequestHandlerInterface
    {
        $factory = $this->factory;

        return new class ($factory, $status) implements RequestHandlerInterface {
            public function __construct(
                private Psr17Factory $factory,
                private int $status,
            ) {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->factory->createResponse($this->status);
            }
        };
    }

    private function kernel(
        RequestHandlerInterface $app,
        ?CustomPermalinkResolver $customPermalink = null,
        ?RenderPublicHomeHandler $frontPage = null,
    ): SingleOriginKernel {
        return new SingleOriginKernel(
            $app,
            $customPermalink ?? new CustomPermalinkResolver($this->nullPermalinkRenderer()),
            new UrlRedirectResolver($this->redirects, $this->factory),
            $frontPage ?? $this->frontPage(false),
            new SpaShellFallback($this->shellPath, $this->factory, $this->factory),
        );
    }

    /**
     * A front-page edge layer. With `$withRecord` it pins a published record and its fake
     * renderer tags the response so tests can assert the home was SSR'd; without it the
     * layer resolves nothing and passes through (the default for the other tests).
     */
    private function frontPage(bool $withRecord): RenderPublicHomeHandler
    {
        $settings = new InMemorySettingRepository([new SettingDef('front_page', 'text', '', true, 'Front page')]);
        $entities = new InMemoryEntityRepository();
        $entityTypes = new InMemoryEntityTypeRepository();

        if ($withRecord) {
            $settings->applyValueDirect('front_page', '5', null);
            $entities = new InMemoryEntityRepository([
                new Entity(id: 5, entityTypeId: 2, slug: 'about', status: EntityStatus::Published, publishedAt: new DateTimeImmutable('2026-06-01 00:00:00')),
            ]);
            $entityTypes = new InMemoryEntityTypeRepository([
                new EntityType(name: 'Pages', slug: 'pages', id: 2, permalinkPattern: '/{type}/{slug}'),
            ]);
        }

        $renderer = new class ($this->factory) implements PublicRecordViewRendererInterface {
            public function __construct(private Psr17Factory $factory)
            {
            }

            public function renderEntity(string $typeSlug, ?string $entitySlug, ?int $entityId, ServerRequestInterface $request, bool $asFrontPage = false): ResponseInterface
            {
                return $this->factory->createResponse(200)
                    ->withHeader('Content-Type', 'text/html; charset=utf-8')
                    ->withHeader('X-Front-Page', $asFrontPage ? '1' : '0')
                    ->withBody($this->factory->createStream('<!doctype html><title>FRONT</title>'));
            }
        };

        return new RenderPublicHomeHandler(new FrontPageSetting($settings, $entities, $entityTypes), $renderer);
    }

    /** A renderer that never matches — the default for tests not exercising permalinks. */
    private function nullPermalinkRenderer(): PublicPermalinkRendererInterface
    {
        return new class () implements PublicPermalinkRendererInterface {
            public function renderByPermalink(string $path, ServerRequestInterface $request): ?ResponseInterface
            {
                return null;
            }
        };
    }

    public function testPassesThroughNon404Responses(): void
    {
        $response = $this->kernel($this->appReturning(200))
            ->handle($this->factory->createServerRequest('GET', 'https://site.test/posts/1'));

        self::assertSame(200, $response->getStatusCode());
    }

    public function testRedirectTakesPrecedenceOverShellOn404(): void
    {
        $this->redirects->save('/2024/01/hello-world', '/posts/1');

        $request = $this->factory
            ->createServerRequest('GET', 'https://site.test/2024/01/hello-world')
            ->withHeader('Accept', 'text/html');

        $response = $this->kernel($this->appReturning(404))->handle($request);

        self::assertSame(301, $response->getStatusCode());
        self::assertSame('/posts/1', $response->getHeaderLine('Location'));
    }

    public function testFallsBackToShellWhenNoRedirectMatches(): void
    {
        $request = $this->factory
            ->createServerRequest('GET', 'https://site.test/admin/dashboard')
            ->withHeader('Accept', 'text/html');

        $response = $this->kernel($this->appReturning(404))->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        self::assertStringContainsString('id="root"', (string) $response->getBody());
    }

    public function testKeepsGenuine404ForApiPaths(): void
    {
        $request = $this->factory
            ->createServerRequest('GET', 'https://site.test/api/v1/unknown')
            ->withHeader('Accept', 'application/json');

        $response = $this->kernel($this->appReturning(404))->handle($request);

        self::assertSame(404, $response->getStatusCode());
    }

    public function testCustomPermalinkResolvesDeepPathOn404(): void
    {
        // A 3-segment custom permalink matches no fixed-arity catch-all route, so the
        // app 404s; the custom-permalink edge layer then serves the record (#651).
        $factory = $this->factory;
        $renderer = new class ($factory) implements PublicPermalinkRendererInterface {
            public function __construct(private Psr17Factory $factory)
            {
            }

            public function renderByPermalink(string $path, ServerRequestInterface $request): ?ResponseInterface
            {
                return $path === '/company/about/team'
                    ? $this->factory->createResponse(200)->withHeader('X-Resolved', 'custom-permalink')
                    : null;
            }
        };

        $response = $this->kernel($this->appReturning(404), new CustomPermalinkResolver($renderer))
            ->handle($this->factory->createServerRequest('GET', 'https://site.test/company/about/team'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('custom-permalink', $response->getHeaderLine('X-Resolved'));
    }

    public function testCustomPermalinkTakesPrecedenceOverRedirectOn404(): void
    {
        // A live record at the path wins over a stale 301 whose source equals it.
        $this->redirects->save('/company/about/team', '/somewhere-else');
        $factory = $this->factory;
        $renderer = new class ($factory) implements PublicPermalinkRendererInterface {
            public function __construct(private Psr17Factory $factory)
            {
            }

            public function renderByPermalink(string $path, ServerRequestInterface $request): ?ResponseInterface
            {
                return $path === '/company/about/team' ? $this->factory->createResponse(200) : null;
            }
        };

        $response = $this->kernel($this->appReturning(404), new CustomPermalinkResolver($renderer))
            ->handle($this->factory->createServerRequest('GET', 'https://site.test/company/about/team'));

        self::assertSame(200, $response->getStatusCode());
    }

    public function testCustomPermalinkLayerFallsThroughToShellWhenNoMatch(): void
    {
        // No record claims the path → the layer passes through to the SPA shell, so
        // ordinary client routes are never hijacked.
        $request = $this->factory
            ->createServerRequest('GET', 'https://site.test/admin/dashboard')
            ->withHeader('Accept', 'text/html');

        $response = $this->kernel($this->appReturning(404))->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('id="root"', (string) $response->getBody());
    }

    public function testFrontPageRecordIsServedAtRoot(): void
    {
        // A pinned front page turns `/` into a server-rendered record (kept by the shell).
        $request = $this->factory
            ->createServerRequest('GET', 'https://site.test/')
            ->withHeader('Accept', 'text/html');

        $response = $this->kernel($this->appReturning(200), null, $this->frontPage(true))->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('1', $response->getHeaderLine('X-Front-Page'));
        self::assertStringContainsString('FRONT', (string) $response->getBody());
    }

    public function testUpstreamThrottleAtRootIsNotMaskedByFrontPageSsr(): void
    {
        // A throttled `/` must stay 429 (Retry-After intact): the front-page layer only
        // takes over the framework's 200 info payload, so rate limiting keeps its bite
        // and `/` never becomes a throttle-free multi-query endpoint.
        $request = $this->factory
            ->createServerRequest('GET', 'https://site.test/')
            ->withHeader('Accept', 'text/html');
        $upstream = $this->factory->createResponse(429)->withHeader('Retry-After', '60');

        $response = $this->frontPage(true)->apply($request, $upstream);

        self::assertSame(429, $response->getStatusCode());
        self::assertSame('60', $response->getHeaderLine('Retry-After'));
        self::assertSame('', $response->getHeaderLine('X-Front-Page'));
    }

    public function testUpstreamErrorAtRootIsNotMaskedByFrontPageSsr(): void
    {
        // A temporary upstream 5xx keeps signalling failure instead of a fresh 200 SSR.
        $request = $this->factory
            ->createServerRequest('GET', 'https://site.test/')
            ->withHeader('Accept', 'text/html');

        $response = $this->frontPage(true)->apply($request, $this->factory->createResponse(500));

        self::assertSame(500, $response->getStatusCode());
        self::assertSame('', $response->getHeaderLine('X-Front-Page'));
    }

    public function testUpstreamHtmlAtRootPassesThroughFrontPageLayer(): void
    {
        // An upstream handler that already produced an HTML page for `/` is the real
        // answer — the layer must not render a second page over it.
        $request = $this->factory
            ->createServerRequest('GET', 'https://site.test/')
            ->withHeader('Accept', 'text/html');
        $upstream = $this->factory->createResponse(200)
            ->withHeader('Content-Type', 'text/html; charset=utf-8');

        $response = $this->frontPage(true)->apply($request, $upstream);

        self::assertSame($upstream, $response);
    }

    public function testRootWithoutFrontPageFallsBackToShell(): void
    {
        // App answers 200 (framework-info-shaped) and nothing is pinned, so the SPA shell
        // still renders the default home at `/`.
        $request = $this->factory
            ->createServerRequest('GET', 'https://site.test/')
            ->withHeader('Accept', 'text/html');

        $response = $this->kernel($this->appReturning(200))->handle($request);

        self::assertStringContainsString('id="root"', (string) $response->getBody());
    }
}
