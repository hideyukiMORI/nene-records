<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\FieldDef;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use NeNeRecords\EntityType\EntityType;
use NeNeRecords\EntityType\EntityTypeNotFoundExceptionHandler;
use NeNeRecords\FieldDef\CreateFieldDefHandler;
use NeNeRecords\FieldDef\CreateFieldDefUseCase;
use NeNeRecords\FieldDef\DeleteFieldDefHandler;
use NeNeRecords\FieldDef\DeleteFieldDefUseCase;
use NeNeRecords\FieldDef\FieldDef;
use NeNeRecords\FieldDef\FieldDefConflictExceptionHandler;
use NeNeRecords\FieldDef\FieldDefNotFoundExceptionHandler;
use NeNeRecords\FieldDef\FieldDefRouteRegistrar;
use NeNeRecords\FieldDef\GetFieldDefByIdHandler;
use NeNeRecords\FieldDef\GetFieldDefByIdUseCase;
use NeNeRecords\FieldDef\ListFieldDefsHandler;
use NeNeRecords\FieldDef\ListFieldDefsUseCase;
use NeNeRecords\FieldDef\UpdateFieldDefHandler;
use NeNeRecords\FieldDef\UpdateFieldDefUseCase;
use NeNeRecords\Tests\EntityType\InMemoryEntityTypeRepository;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class FieldDefHttpTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryEntityTypeRepository $entityTypes;
    private InMemoryFieldDefRepository $fieldDefs;
    private RequestHandlerInterface $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();
        $this->entityTypes = new InMemoryEntityTypeRepository();
        $this->fieldDefs = new InMemoryFieldDefRepository();

        $jsonResponse = new JsonResponseFactory($this->factory, $this->factory);
        $problemDetails = new ProblemDetailsResponseFactory($this->factory, $this->factory);

        $registrar = new FieldDefRouteRegistrar(
            new GetFieldDefByIdHandler(new GetFieldDefByIdUseCase($this->fieldDefs), $jsonResponse),
            new CreateFieldDefHandler(new CreateFieldDefUseCase($this->fieldDefs, $this->entityTypes), $jsonResponse),
            new UpdateFieldDefHandler(new UpdateFieldDefUseCase($this->fieldDefs, $this->entityTypes), $jsonResponse),
            new DeleteFieldDefHandler(new DeleteFieldDefUseCase($this->fieldDefs), $this->factory),
            new ListFieldDefsHandler(new ListFieldDefsUseCase($this->fieldDefs), $jsonResponse),
        );

        $this->application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [
                new EntityTypeNotFoundExceptionHandler($problemDetails),
                new FieldDefNotFoundExceptionHandler($problemDetails),
                new FieldDefConflictExceptionHandler($problemDetails),
            ],
            routeRegistrars: [$registrar],
        ))->create();
    }

    public function testPostFieldDefCreatesDefinitionAndReturns201WithLocation(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Article', slug: 'article'));

        $body = $this->factory->createStream(json_encode([
            'entity_type_id' => $typeId,
            'field_key' => 'title',
            'data_type' => 'text',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/field-defs')->withBody($body),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(201, $response->getStatusCode());
        self::assertStringStartsWith('/api/v1/field-defs/', $response->getHeaderLine('Location'));
        self::assertSame($typeId, $payload['entity_type_id']);
        self::assertSame('title', $payload['field_key']);
        self::assertSame('text', $payload['data_type']);
        self::assertIsInt($payload['id']);
    }

    public function testPostDuplicateKeyReturns409WithConflictProblemType(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Article', slug: 'article'));
        $this->fieldDefs->save(new FieldDef(entityTypeId: $typeId, fieldKey: 'title', dataType: 'text'));

        $body = $this->factory->createStream(json_encode([
            'entity_type_id' => $typeId,
            'field_key' => 'title',
            'data_type' => 'text',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/field-defs')->withBody($body),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(409, $response->getStatusCode());
        self::assertStringEndsWith('field-def-conflict', (string) $payload['type']);
    }

    public function testPostUnknownEntityTypeReturns404(): void
    {
        $body = $this->factory->createStream(json_encode([
            'entity_type_id' => 99,
            'field_key' => 'title',
            'data_type' => 'text',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/field-defs')->withBody($body),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(404, $response->getStatusCode());
        self::assertStringEndsWith('not-found', (string) $payload['type']);
    }

    public function testGetFieldDefByIdReturnsDefinition(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Article', slug: 'article'));
        $id = $this->fieldDefs->save(new FieldDef(entityTypeId: $typeId, fieldKey: 'title', dataType: 'text'));

        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/field-defs/{$id}"),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame($id, $payload['id']);
        self::assertSame('title', $payload['field_key']);
    }

    public function testDeleteFieldDefReturns204AndSoftDeletes(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Article', slug: 'article'));
        $id = $this->fieldDefs->save(new FieldDef(entityTypeId: $typeId, fieldKey: 'title', dataType: 'text'));

        $response = $this->application->handle(
            $this->factory->createServerRequest('DELETE', "https://example.test/api/v1/field-defs/{$id}"),
        );

        self::assertSame(204, $response->getStatusCode());
        self::assertNull($this->fieldDefs->findById($id));
    }

    public function testListFieldDefsFiltersByEntityTypeId(): void
    {
        $typeA = $this->entityTypes->save(new EntityType(name: 'A', slug: 'a'));
        $typeB = $this->entityTypes->save(new EntityType(name: 'B', slug: 'b'));
        $this->fieldDefs->save(new FieldDef(entityTypeId: $typeA, fieldKey: 'title', dataType: 'text'));
        $this->fieldDefs->save(new FieldDef(entityTypeId: $typeB, fieldKey: 'body', dataType: 'text'));

        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/field-defs?entity_type_id={$typeA}"),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(1, $payload['items']);
        self::assertSame('title', $payload['items'][0]['field_key']);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(ResponseInterface $response): array
    {
        $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($payload);

        return $payload;
    }
}
