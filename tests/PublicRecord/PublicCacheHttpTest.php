<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PublicRecord;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\EntityType\EntityType;
use NeNeRecords\FieldDef\FieldDef;
use NeNeRecords\NavigationItem\ListNavigationItemsUseCase;
use NeNeRecords\NavigationItem\ListPublicNavigationItemsHandler;
use NeNeRecords\NavigationItem\NavigationItem;
use NeNeRecords\NavigationItem\NavigationItemRouteRegistrar;
use NeNeRecords\PublicRecord\GetPublicRecordViewHandler;
use NeNeRecords\PublicRecord\GetPublicRecordViewUseCase;
use NeNeRecords\PublicRecord\PublicEntityTypeNotFoundExceptionHandler;
use NeNeRecords\PublicRecord\PublicRecordNotFoundExceptionHandler;
use NeNeRecords\PublicRecord\PublicRecordRouteRegistrar;
use NeNeRecords\Setting\ListPublicSettingsHandler;
use NeNeRecords\Setting\ListPublicSettingsUseCase;
use NeNeRecords\Setting\SettingRouteRegistrar;
use NeNeRecords\Tests\BoolField\InMemoryBoolFieldRepository;
use NeNeRecords\Tests\DateTimeField\InMemoryDateTimeFieldRepository;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\EntityRelation\InMemoryEntityRelationRepository;
use NeNeRecords\Tests\EntityType\InMemoryEntityTypeRepository;
use NeNeRecords\Tests\EnumField\InMemoryEnumFieldRepository;
use NeNeRecords\Tests\FieldDef\InMemoryFieldDefRepository;
use NeNeRecords\Tests\IntField\InMemoryIntFieldRepository;
use NeNeRecords\Tests\NavigationItem\InMemoryNavigationItemRepository;
use NeNeRecords\Tests\Setting\InMemorySettingRepository;
use NeNeRecords\Tests\TextField\InMemoryTextFieldRepository;
use NeNeRecords\TextField\TextField;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Tests Cache-Control and ETag behaviour for public consumer endpoints.
 */
final class PublicCacheHttpTest extends TestCase
{
    private Psr17Factory $factory;
    private RequestHandlerInterface $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();

        $entityTypes = new InMemoryEntityTypeRepository([
            new EntityType(name: 'Article', slug: 'article', id: 1),
        ]);
        $entities = new InMemoryEntityRepository([
            new Entity(id: 10, entityTypeId: 1, slug: 'hello-world', status: EntityStatus::Published),
        ]);
        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'title', dataType: 'text', id: 1),
        ]);
        $textFields = new InMemoryTextFieldRepository([
            new TextField(entityId: 10, fieldKey: 'title', value: 'Hello world', id: 1),
        ], $entities);

        $settingRepo = new InMemorySettingRepository();
        $publicSettings = new ListPublicSettingsUseCase($settingRepo);

        $navRepo = new InMemoryNavigationItemRepository();
        $navRepo->save(new NavigationItem(id: null, label: 'Home', url: '/', location: 'header', displayOrder: 0, createdAt: '2026-01-01T00:00:00Z', updatedAt: '2026-01-01T00:00:00Z'));
        $navUseCase = new ListNavigationItemsUseCase($navRepo);

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

        $publicRecordRegistrar = new PublicRecordRouteRegistrar(
            new GetPublicRecordViewHandler($useCase, $jsonResponse, $this->factory),
            // RenderPublicRecordViewHandler is not needed for these tests; use a no-op placeholder via the JSON handler
            new \NeNeRecords\PublicRecord\RenderPublicRecordViewHandler(
                $useCase,
                $publicSettings,
                new \Nene2\View\HtmlResponseFactory($this->factory, $this->factory, new \Nene2\View\NativePhpViewRenderer(dirname(__DIR__, 2) . '/templates')),
                new \Nene2\Config\AppConfig(
                    environment: \Nene2\Config\AppEnvironment::Test,
                    debug: true,
                    name: 'Test',
                    database: new \Nene2\Config\DatabaseConfig(null, 'test', 'sqlite', '', 1, ':memory:', '', '', ''),
                    machineApiKey: null,
                ),
            ),
        );

        $settingRegistrar = new SettingRouteRegistrar(
            new \NeNeRecords\Setting\ListSettingsHandler(new \NeNeRecords\Setting\ListSettingsUseCase($settingRepo), $jsonResponse),
            new ListPublicSettingsHandler($publicSettings, $jsonResponse, $this->factory),
            new \NeNeRecords\Setting\UpdateSettingHandler(new \NeNeRecords\Tests\Setting\InMemoryUpdateSettingUseCase($settingRepo), $jsonResponse),
            new \NeNeRecords\Setting\ListSettingRevisionsHandler(new \NeNeRecords\Setting\ListSettingRevisionsUseCase($settingRepo), $jsonResponse),
        );

        $navRegistrar = new NavigationItemRouteRegistrar(
            new \NeNeRecords\NavigationItem\ListNavigationItemsHandler($navUseCase, $jsonResponse),
            new ListPublicNavigationItemsHandler($navUseCase, $jsonResponse, $this->factory),
            new \NeNeRecords\NavigationItem\CreateNavigationItemHandler(new \NeNeRecords\NavigationItem\CreateNavigationItemUseCase($navRepo), $jsonResponse),
            new \NeNeRecords\NavigationItem\UpdateNavigationItemHandler(new \NeNeRecords\NavigationItem\UpdateNavigationItemUseCase($navRepo), $jsonResponse),
            new \NeNeRecords\NavigationItem\DeleteNavigationItemHandler(new \NeNeRecords\NavigationItem\DeleteNavigationItemUseCase($navRepo), $jsonResponse),
        );

        $this->application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [
                new PublicEntityTypeNotFoundExceptionHandler($problemDetails),
                new PublicRecordNotFoundExceptionHandler($problemDetails),
            ],
            routeRegistrars: [$publicRecordRegistrar, $settingRegistrar, $navRegistrar],
        ))->create();
    }

    // ── GET /api/v1/public/entity-types/{slug}/records/{entitySlug} ────────

    public function testPublicRecordResponseHasCacheControlHeader(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/public/entity-types/article/records/hello-world'),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('public', $response->getHeaderLine('Cache-Control'));
        self::assertStringContainsString('max-age=60', $response->getHeaderLine('Cache-Control'));
        self::assertNotEmpty($response->getHeaderLine('ETag'));
    }

    public function testPublicRecordReturns304OnMatchingEtag(): void
    {
        $first = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/public/entity-types/article/records/hello-world'),
        );

        $etag = $first->getHeaderLine('ETag');
        self::assertNotEmpty($etag);

        $second = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/public/entity-types/article/records/hello-world')
                ->withHeader('If-None-Match', $etag),
        );

        self::assertSame(304, $second->getStatusCode());
        self::assertSame($etag, $second->getHeaderLine('ETag'));
    }

    public function testPublicRecordDoesNotReturn304OnMismatchedEtag(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/public/entity-types/article/records/hello-world')
                ->withHeader('If-None-Match', '"stale-etag"'),
        );

        self::assertSame(200, $response->getStatusCode());
    }

    // ── GET /api/v1/public/settings ────────────────────────────────────────

    public function testPublicSettingsResponseHasCacheControlHeader(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/public/settings'),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('public', $response->getHeaderLine('Cache-Control'));
        self::assertStringContainsString('max-age=300', $response->getHeaderLine('Cache-Control'));
        self::assertNotEmpty($response->getHeaderLine('ETag'));
    }

    public function testPublicSettingsReturns304OnMatchingEtag(): void
    {
        $first = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/public/settings'),
        );

        $etag = $first->getHeaderLine('ETag');
        self::assertNotEmpty($etag);

        $second = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/public/settings')
                ->withHeader('If-None-Match', $etag),
        );

        self::assertSame(304, $second->getStatusCode());
    }

    // ── GET /api/v1/public/navigation-items ────────────────────────────────

    public function testPublicNavigationItemsResponseHasCacheControlHeader(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/public/navigation-items'),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('public', $response->getHeaderLine('Cache-Control'));
        self::assertStringContainsString('max-age=300', $response->getHeaderLine('Cache-Control'));
        self::assertNotEmpty($response->getHeaderLine('ETag'));
    }

    public function testPublicNavigationItemsReturns304OnMatchingEtag(): void
    {
        $first = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/public/navigation-items'),
        );

        $etag = $first->getHeaderLine('ETag');

        $second = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/public/navigation-items')
                ->withHeader('If-None-Match', $etag),
        );

        self::assertSame(304, $second->getStatusCode());
    }

    public function testAdminNavigationItemsHasNoCacheHeaders(): void
    {
        // The admin endpoint /api/v1/navigation-items should NOT have Cache-Control
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/navigation-items'),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('', $response->getHeaderLine('Cache-Control'));
    }
}
