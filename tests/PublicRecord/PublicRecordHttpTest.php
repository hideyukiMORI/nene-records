<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PublicRecord;

use DateTimeImmutable;
use Nene2\Config\AppConfig;
use Nene2\Config\AppEnvironment;
use Nene2\Config\DatabaseConfig;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use Nene2\View\HtmlResponseFactory;
use Nene2\View\NativePhpViewRenderer;
use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\EntityType\EntityType;
use NeNeRecords\FieldDef\FieldDef;
use NeNeRecords\PublicRecord\GenerateSitemapUseCase;
use NeNeRecords\PublicRecord\GetPublicRecordViewHandler;
use NeNeRecords\PublicRecord\GetPublicRecordViewUseCase;
use NeNeRecords\PublicRecord\PublicEntityTypeNotFoundExceptionHandler;
use NeNeRecords\PublicRecord\PublicHtmlSanitizer;
use NeNeRecords\PublicRecord\PublicRecordNotFoundExceptionHandler;
use NeNeRecords\PublicRecord\PublicRecordRouteRegistrar;
use NeNeRecords\PublicRecord\RenderPublicPermalinkHandler;
use NeNeRecords\PublicRecord\RenderPublicRecordViewHandler;
use NeNeRecords\PublicRecord\RenderRobotsHandler;
use NeNeRecords\PublicRecord\RenderSitemapHandler;
use NeNeRecords\Setting\ListPublicSettingsUseCase;
use NeNeRecords\Tests\BoolField\InMemoryBoolFieldRepository;
use NeNeRecords\Tests\DateTimeField\InMemoryDateTimeFieldRepository;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\EntityRelation\InMemoryEntityRelationRepository;
use NeNeRecords\Tests\EntityType\InMemoryEntityTypeRepository;
use NeNeRecords\Tests\EnumField\InMemoryEnumFieldRepository;
use NeNeRecords\Tests\FieldDef\InMemoryFieldDefRepository;
use NeNeRecords\Tests\IntField\InMemoryIntFieldRepository;
use NeNeRecords\Tests\Media\InMemoryMediaRepository;
use NeNeRecords\Tests\Setting\InMemorySettingRepository;
use NeNeRecords\Tests\TextField\InMemoryTextFieldRepository;
use NeNeRecords\TextField\TextField;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class PublicRecordHttpTest extends TestCase
{
    private Psr17Factory $factory;
    private RequestHandlerInterface $application;
    private string $projectRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();
        $this->projectRoot = dirname(__DIR__, 2);
        $this->application = $this->buildApplication(true, $this->projectRoot);
    }

    /** @param list<\NeNeRecords\Setting\SettingDef> $settingDefs */
    private function buildApplication(bool $debug, string $projectRoot, array $settingDefs = []): RequestHandlerInterface
    {
        $entityTypes = new InMemoryEntityTypeRepository([
            new EntityType(name: 'Article', slug: 'article', id: 1),
        ]);
        $entities = new InMemoryEntityRepository([
            new Entity(
                id: 10,
                entityTypeId: 1,
                slug: 'hello-world',
                status: EntityStatus::Published,
                publishedAt: new DateTimeImmutable('2026-01-15T00:00:00+00:00'),
                updatedAt: new DateTimeImmutable('2026-02-20T00:00:00+00:00'),
                metaDescription: 'A short summary.',
            ),
        ]);
        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'title', dataType: 'text', id: 1),
            new FieldDef(entityTypeId: 1, fieldKey: 'body', dataType: 'text', id: 2),
            new FieldDef(entityTypeId: 1, fieldKey: 'hero', dataType: 'image', id: 3),
            new FieldDef(entityTypeId: 1, fieldKey: 'richbody', dataType: 'html', id: 4),
        ]);
        $textFields = new InMemoryTextFieldRepository([
            new TextField(entityId: 10, fieldKey: 'title', value: 'Hello world', id: 1),
            new TextField(entityId: 10, fieldKey: 'body', value: "## Sample\n\n**bold** line", id: 2),
            new TextField(entityId: 10, fieldKey: 'hero', value: '/media/2026/06/hero.png', id: 3),
            new TextField(
                entityId: 10,
                fieldKey: 'richbody',
                value: '<p>imported <strong>kept</strong></p><img src="/media/imported/x.jpg" alt="p" /><script>alert(1)</script>',
                id: 4,
            ),
        ], $entities);

        $publicSettings = new ListPublicSettingsUseCase(new InMemorySettingRepository($settingDefs), new InMemoryMediaRepository());

        $useCase = new GetPublicRecordViewUseCase(
            $entityTypes,
            $entities,
            $fieldDefs,
            $textFields,
            new InMemoryIntFieldRepository(),
            new InMemoryEnumFieldRepository(),
            new InMemoryBoolFieldRepository(),
            new InMemoryDateTimeFieldRepository(),
            new InMemoryEntityRelationRepository(),
            $publicSettings,
        );

        $jsonResponse = new JsonResponseFactory($this->factory, $this->factory);
        $problemDetails = new ProblemDetailsResponseFactory($this->factory, $this->factory);
        $renderer = new NativePhpViewRenderer(dirname(__DIR__, 2) . '/templates');
        $htmlResponse = new HtmlResponseFactory($this->factory, $this->factory, $renderer);
        $config = new AppConfig(
            environment: AppEnvironment::Test,
            debug: $debug,
            name: 'NeNe Records',
            database: new DatabaseConfig(
                url: null,
                environment: 'test',
                adapter: 'sqlite',
                host: '',
                port: 1,
                name: ':memory:',
                user: '',
                password: '',
                charset: '',
            ),
            machineApiKey: null,
        );

        $renderHandler = new RenderPublicRecordViewHandler($useCase, $publicSettings, $htmlResponse, $config, $projectRoot, $this->factory, new PublicHtmlSanitizer());
        $registrar = new PublicRecordRouteRegistrar(
            new GetPublicRecordViewHandler($useCase, $jsonResponse, $this->factory),
            $renderHandler,
            new RenderPublicPermalinkHandler($entityTypes, $renderHandler),
            new RenderSitemapHandler(
                new GenerateSitemapUseCase($entityTypes, $entities),
                $this->factory,
                $this->factory,
            ),
            new RenderRobotsHandler($this->factory, $this->factory),
        );

        return (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [
                new PublicEntityTypeNotFoundExceptionHandler($problemDetails),
                new PublicRecordNotFoundExceptionHandler($problemDetails),
            ],
            routeRegistrars: [$registrar],
        ))->create();
    }

    public function testGetPublicRecordViewReturnsAggregatedJson(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest(
                'GET',
                'https://example.test/api/v1/public/entity-types/article/records/hello-world',
            ),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('article', $payload['entityTypeSlug']);
        self::assertSame(10, $payload['entityId']);
        self::assertSame('Hello world', $payload['textFields']['items'][0]['value']);
    }

    public function testGetPublicRecordViewReturns404ForUnknownSlug(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest(
                'GET',
                'https://example.test/api/v1/public/entity-types/missing/records/some-slug',
            ),
        );

        self::assertSame(404, $response->getStatusCode());
    }

    public function testRenderPublicRecordViewSanitizesHtmlFieldsServerSide(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/article/10'),
        );
        $html = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());

        // Inspect only the rendered <article> (the crawler-visible content), not the
        // hydration bootstrap JSON below it (which carries the raw value as inert,
        // hex-escaped data for the SPA to sanitize via DOMPurify).
        $start = strpos($html, '<article>');
        $end = strpos($html, '</article>');
        self::assertNotFalse($start);
        self::assertNotFalse($end);
        $article = substr($html, $start, $end - $start);

        // html-typed field: rich markup + images survive in the crawlable SSR…
        self::assertStringContainsString('<strong>kept</strong>', $article);
        self::assertStringContainsString('/media/imported/x.jpg', $article);
        // …but scripts/handlers are stripped server-side (no arbitrary JS in the article).
        self::assertStringNotContainsString('<script', $article);
        self::assertStringNotContainsString('alert(1)', $article);
    }

    public function testRenderPublicRecordViewReturnsHtmlWithBootstrapAndArticleContent(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest(
                'GET',
                'https://example.test/article/10',
            ),
        );
        $html = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        self::assertStringContainsString('<h1>Hello world</h1>', $html);
        self::assertStringContainsString('id="nene-records-public-record-bootstrap"', $html);
        self::assertStringContainsString('"entityTypeSlug":"article"', $html);
        self::assertStringContainsString('<h2>Sample</h2>', $html);
        self::assertStringContainsString('<strong>bold</strong>', $html);
    }

    public function testRenderPublicRecordViewIncludesSeoHeadTags(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest(
                'GET',
                'https://example.test/article/10',
            ),
        );
        $html = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        // Canonical / og:url point at the user-facing permalink (default /{type}/{id}), not /view/.
        self::assertStringContainsString('<link rel="canonical" href="https://example.test/article/10" />', $html);
        self::assertStringContainsString('content="https://example.test/article/10"', $html);
        // Open Graph
        self::assertStringContainsString('property="og:type" content="article"', $html);
        self::assertStringContainsString('property="og:title" content="Hello world"', $html);
        self::assertStringContainsString('property="og:description" content="A short summary."', $html);
        self::assertStringContainsString('property="og:site_name" content="NeNe Records"', $html);
        // Twitter Card — large image because the record has an image field
        self::assertStringContainsString('name="twitter:card" content="summary_large_image"', $html);
        self::assertStringContainsString('name="twitter:title" content="Hello world"', $html);
        // og:image / twitter:image resolve the image field to the `og` derivative (absolute)
        self::assertStringContainsString('property="og:image" content="https://example.test/media/og/2026/06/hero.png"', $html);
        self::assertStringContainsString('name="twitter:image" content="https://example.test/media/og/2026/06/hero.png"', $html);
        // Per-entity meta description (not the site default)
        self::assertStringContainsString('<meta name="description" content="A short summary." />', $html);
        // JSON-LD structured data
        self::assertStringContainsString('application/ld+json', $html);
        self::assertStringContainsString('"@type":"BlogPosting"', $html);
        self::assertStringContainsString('"headline":"Hello world"', $html);
        self::assertStringContainsString('"datePublished":"2026-01-15T00:00:00+00:00"', $html);
        self::assertStringContainsString('"dateModified":"2026-02-20T00:00:00+00:00"', $html);
        self::assertStringContainsString('"image":"https://example.test/media/og/2026/06/hero.png"', $html);
    }

    public function testViewUrlRedirectsToCanonicalPermalink(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/view/article/hello-world'),
        );

        self::assertSame(301, $response->getStatusCode());
        self::assertSame('https://example.test/article/10', $response->getHeaderLine('Location'));
    }

    public function testRealPermalinkServesCrawlableHtmlByIdPattern(): void
    {
        // The "article" type uses the default pattern /{type}/{id}, so /article/10 is the permalink.
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/article/10'),
        );
        $html = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        self::assertStringContainsString('<h1>Hello world</h1>', $html);
        // Canonical/og still point at the same real permalink.
        self::assertStringContainsString('<link rel="canonical" href="https://example.test/article/10" />', $html);
        self::assertStringContainsString('id="nene-records-public-record-bootstrap"', $html);
        // SPA-aware CSP: inline styles (runtime theme) + data: fonts are allowed.
        $csp = $response->getHeaderLine('Content-Security-Policy');
        self::assertStringContainsString("style-src 'self' 'unsafe-inline'", $csp);
        self::assertStringContainsString("font-src 'self' data:", $csp);
    }

    public function testSsrInjectsAnalyticsWhenConfigured(): void
    {
        // A public GA4 setting whose effective value is a valid measurement id.
        $app = $this->buildApplication(true, $this->projectRoot, [
            new \NeNeRecords\Setting\SettingDef('analytics_ga4_id', 'text', 'G-SSRTEST1', true, 'GA4'),
            new \NeNeRecords\Setting\SettingDef('analytics_consent_default', 'text', 'denied', true, 'Consent'),
        ]);

        $response = $app->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/article/10'),
        );
        $html = (string) $response->getBody();
        $csp = $response->getHeaderLine('Content-Security-Policy');

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('gtag/js?id=G-SSRTEST1', $html);
        self::assertStringContainsString("'analytics_storage':'denied'", $html);
        self::assertStringContainsString('https://www.googletagmanager.com', $csp);

        // CSP nonce must match the nonce on the injected script.
        preg_match('/nonce="([0-9a-f]{32})"/', $html, $m);
        $nonce = $m[1] ?? '';
        self::assertNotSame('', $nonce);
        self::assertStringContainsString("'nonce-{$nonce}'", $csp);
    }

    public function testSsrKeepsStrictCspWhenAnalyticsDisabled(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/article/10'),
        );
        $csp = $response->getHeaderLine('Content-Security-Policy');

        self::assertStringNotContainsString('googletagmanager', $csp);
        self::assertStringNotContainsString('googletagmanager', (string) $response->getBody());
    }

    public function testSitemapXmlListsHomeAndPublishedRecords(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/sitemap.xml'),
        );
        $xml = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('application/xml', $response->getHeaderLine('Content-Type'));
        self::assertStringContainsString(
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
            $xml,
        );
        self::assertStringContainsString('<loc>https://example.test/</loc>', $xml);
        // The published article resolves through the default /{type}/{id} permalink.
        self::assertStringContainsString('<loc>https://example.test/article/10</loc>', $xml);
        // updatedAt → lastmod (W3C datetime).
        self::assertStringContainsString('<lastmod>2026-02-20', $xml);
    }

    public function testRobotsTxtServesDirectivesAndSitemapPointer(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/robots.txt'),
        );
        $body = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('text/plain', $response->getHeaderLine('Content-Type'));
        self::assertStringContainsString('User-agent: *', $body);
        self::assertStringContainsString('Disallow: /admin', $body);
        self::assertStringContainsString('Sitemap: https://example.test/sitemap.xml', $body);
    }

    public function testRealPermalinkReturns404ForUnknownId(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/article/999'),
        );

        self::assertSame(404, $response->getStatusCode());
    }

    public function testRealPermalinkReturns404ForUnknownType(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/missing/10'),
        );

        self::assertSame(404, $response->getStatusCode());
    }

    public function testDevModeWrapsSsrContentInRootAndLoadsViteClient(): void
    {
        // Default app is debug=true → SSR content lives inside #root and the SPA
        // mounts via the Vite dev client (createRoot replaces the SSR fallback).
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/article/10'),
        );
        $html = (string) $response->getBody();

        self::assertStringContainsString('<div id="root">', $html);
        self::assertStringContainsString('<h1>Hello world</h1>', $html);
        self::assertStringContainsString('/@vite/client', $html);
        self::assertStringContainsString('/src/main.tsx', $html);
    }

    public function testProdModeMountsBuiltSpaFromManifest(): void
    {
        // Prod build (debug=false) with a fixture Vite manifest → SSR shell + built
        // SPA entry/css/modulepreload; no Vite dev client.
        $app = $this->buildApplication(false, __DIR__ . '/fixtures/spa-build');
        $response = $app->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/article/10'),
        );
        $html = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('<div id="root">', $html);
        self::assertStringContainsString('<h1>Hello world</h1>', $html);
        // Built entry + stylesheets + preload resolved from the manifest graph.
        self::assertStringContainsString('<script type="module" crossorigin src="/assets/index-ABC.js">', $html);
        self::assertStringContainsString('<link rel="stylesheet" crossorigin href="/assets/index-ABC.css" />', $html);
        self::assertStringContainsString('<link rel="stylesheet" crossorigin href="/assets/vendor-XYZ.css" />', $html);
        self::assertStringContainsString('<link rel="modulepreload" crossorigin href="/assets/vendor-XYZ.js" />', $html);
        // No dev client in prod.
        self::assertStringNotContainsString('/@vite/client', $html);
        self::assertStringNotContainsString('/src/main.tsx', $html);
    }

    /** @return array<string, mixed> */
    private function decodeJson(ResponseInterface $response): array
    {
        $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($payload)) {
            self::fail('Expected JSON object response.');
        }

        return $payload;
    }
}
