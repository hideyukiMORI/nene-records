<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PublicRecord;

use Nene2\Config\AppConfig;
use Nene2\Config\AppEnvironment;
use Nene2\Config\DatabaseConfig;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use Nene2\View\HtmlResponseFactory;
use Nene2\View\NativePhpViewRenderer;
use NeNeRecords\Entity\Entity;
use NeNeRecords\EntityType\EntityType;
use NeNeRecords\FieldDef\FieldDef;
use NeNeRecords\PublicRecord\GetPublicRecordViewHandler;
use NeNeRecords\PublicRecord\GetPublicRecordViewUseCase;
use NeNeRecords\PublicRecord\PublicEntityTypeNotFoundExceptionHandler;
use NeNeRecords\PublicRecord\PublicRecordNotFoundExceptionHandler;
use NeNeRecords\PublicRecord\PublicRecordRouteRegistrar;
use NeNeRecords\PublicRecord\RenderPublicRecordViewHandler;
use NeNeRecords\Setting\ListPublicSettingsUseCase;
use NeNeRecords\Tests\BoolField\InMemoryBoolFieldRepository;
use NeNeRecords\Tests\DateTimeField\InMemoryDateTimeFieldRepository;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\EntityRelation\InMemoryEntityRelationRepository;
use NeNeRecords\Tests\EntityType\InMemoryEntityTypeRepository;
use NeNeRecords\Tests\EnumField\InMemoryEnumFieldRepository;
use NeNeRecords\Tests\FieldDef\InMemoryFieldDefRepository;
use NeNeRecords\Tests\IntField\InMemoryIntFieldRepository;
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

        $entityTypes = new InMemoryEntityTypeRepository([
            new EntityType(name: 'Article', slug: 'article', id: 1),
        ]);
        $entities = new InMemoryEntityRepository([
            new Entity(id: 10, entityTypeId: 1),
        ]);
        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'title', dataType: 'text', id: 1),
            new FieldDef(entityTypeId: 1, fieldKey: 'body', dataType: 'text', id: 2),
        ]);
        $textFields = new InMemoryTextFieldRepository([
            new TextField(entityId: 10, fieldKey: 'title', value: 'Hello world', id: 1),
            new TextField(entityId: 10, fieldKey: 'body', value: "Line one\nLine two", id: 2),
        ], $entities);

        $publicSettings = new ListPublicSettingsUseCase(new InMemorySettingRepository());

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
        $renderer = new NativePhpViewRenderer($this->projectRoot . '/templates');
        $htmlResponse = new HtmlResponseFactory($this->factory, $this->factory, $renderer);
        $config = new AppConfig(
            environment: AppEnvironment::Test,
            debug: true,
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

        $registrar = new PublicRecordRouteRegistrar(
            new GetPublicRecordViewHandler($useCase, $jsonResponse),
            new RenderPublicRecordViewHandler($useCase, $publicSettings, $htmlResponse, $config),
        );

        $this->application = (new RuntimeApplicationFactory(
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
                'https://example.test/api/v1/public/entity-types/article/records/10',
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
                'https://example.test/api/v1/public/entity-types/missing/records/10',
            ),
        );

        self::assertSame(404, $response->getStatusCode());
    }

    public function testRenderPublicRecordViewReturnsHtmlWithBootstrapAndArticleContent(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest(
                'GET',
                'https://example.test/view/article/10',
            ),
        );
        $html = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        self::assertStringContainsString('<h1>Hello world</h1>', $html);
        self::assertStringContainsString('id="nene-records-public-record-bootstrap"', $html);
        self::assertStringContainsString('"entityTypeSlug":"article"', $html);
        self::assertStringContainsString('Line one', $html);
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
