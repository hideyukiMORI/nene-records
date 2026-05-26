<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\BoolField;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use NeNeRecords\BoolField\BoolFieldNotFoundExceptionHandler;
use NeNeRecords\BoolField\BoolFieldRouteRegistrar;
use NeNeRecords\BoolField\CreateBoolFieldHandler;
use NeNeRecords\BoolField\CreateBoolFieldUseCase;
use NeNeRecords\BoolField\DeleteBoolFieldHandler;
use NeNeRecords\BoolField\DeleteBoolFieldUseCase;
use NeNeRecords\BoolField\FieldKeyNotRegisteredExceptionHandler;
use NeNeRecords\BoolField\FieldTypeMismatchExceptionHandler;
use NeNeRecords\BoolField\GetBoolFieldByIdHandler;
use NeNeRecords\BoolField\GetBoolFieldByIdUseCase;
use NeNeRecords\BoolField\ListBoolFieldsHandler;
use NeNeRecords\BoolField\ListBoolFieldsUseCase;
use NeNeRecords\BoolField\UpdateBoolFieldHandler;
use NeNeRecords\BoolField\UpdateBoolFieldUseCase;
use NeNeRecords\Entity\CreateEntityHandler;
use NeNeRecords\Entity\CreateEntityUseCase;
use NeNeRecords\Entity\DeleteEntityHandler;
use NeNeRecords\Entity\DeleteEntityUseCase;
use NeNeRecords\Entity\EntityNotFoundExceptionHandler;
use NeNeRecords\Entity\EntityRouteRegistrar;
use NeNeRecords\Entity\ExportEntitiesHandler;
use NeNeRecords\Entity\GetEntityByIdHandler;
use NeNeRecords\Entity\GetEntityByIdUseCase;
use NeNeRecords\Entity\ListEntitiesHandler;
use NeNeRecords\Entity\ListEntitiesUseCase;
use NeNeRecords\Entity\ListEntityRevisionsHandler;
use NeNeRecords\Entity\ListEntityRevisionsUseCase;
use NeNeRecords\Entity\UpdateEntityHandler;
use NeNeRecords\Entity\UpdateEntityUseCase;
use NeNeRecords\EntityType\EntityType;
use NeNeRecords\EntityType\EntityTypeNotFoundExceptionHandler;
use NeNeRecords\FieldDef\FieldDef;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\EntityType\InMemoryEntityTypeRepository;
use NeNeRecords\Tests\FieldDef\InMemoryFieldDefRepository;
use NeNeRecords\Tests\TextField\InMemoryTextFieldRepository;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class BoolFieldHttpTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryEntityTypeRepository $entityTypes;
    private InMemoryEntityRepository $entities;
    private InMemoryFieldDefRepository $fieldDefs;
    private InMemoryBoolFieldRepository $boolFields;
    private RequestHandlerInterface $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();
        $this->entityTypes = new InMemoryEntityTypeRepository();
        $this->entities = new InMemoryEntityRepository();
        $this->fieldDefs = new InMemoryFieldDefRepository();
        $this->boolFields = new InMemoryBoolFieldRepository();

        $jsonResponse = new JsonResponseFactory($this->factory, $this->factory);
        $problemDetails = new ProblemDetailsResponseFactory($this->factory, $this->factory);

        $entityRegistrar = new EntityRouteRegistrar(
            new GetEntityByIdHandler(new GetEntityByIdUseCase($this->entities), $jsonResponse),
            new CreateEntityHandler(new CreateEntityUseCase($this->entities, $this->entityTypes), $jsonResponse),
            new UpdateEntityHandler(new UpdateEntityUseCase($this->entities, $this->entityTypes), $jsonResponse),
            new DeleteEntityHandler(new DeleteEntityUseCase($this->entities), $this->factory),
            new ListEntitiesHandler(new ListEntitiesUseCase($this->entities), $jsonResponse),
            new ListEntityRevisionsHandler(new ListEntityRevisionsUseCase($this->entities), $jsonResponse),
            new ExportEntitiesHandler($this->entities, new InMemoryTextFieldRepository(), $this->factory),
        );

        $intFieldRegistrar = new BoolFieldRouteRegistrar(
            new ListBoolFieldsHandler(new ListBoolFieldsUseCase($this->boolFields), $jsonResponse),
            new GetBoolFieldByIdHandler(new GetBoolFieldByIdUseCase($this->boolFields), $jsonResponse),
            new CreateBoolFieldHandler(new CreateBoolFieldUseCase($this->boolFields, $this->entities, $this->fieldDefs), $jsonResponse),
            new UpdateBoolFieldHandler(new UpdateBoolFieldUseCase($this->boolFields, $this->entities, $this->fieldDefs), $jsonResponse),
            new DeleteBoolFieldHandler(new DeleteBoolFieldUseCase($this->boolFields), $this->factory),
        );

        $this->application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [
                new EntityTypeNotFoundExceptionHandler($problemDetails),
                new EntityNotFoundExceptionHandler($problemDetails),
                new BoolFieldNotFoundExceptionHandler($problemDetails),
                new FieldKeyNotRegisteredExceptionHandler($problemDetails),
                new FieldTypeMismatchExceptionHandler($problemDetails),
            ],
            routeRegistrars: [$entityRegistrar, $intFieldRegistrar],
        ))->create();
    }

    public function testPostBoolFieldCreatesFieldAndReturns201WithLocation(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Item', slug: 'item'));
        $this->fieldDefs->save(new FieldDef(entityTypeId: $typeId, fieldKey: 'title', dataType: 'bool'));

        $bodyEntity = $this->factory->createStream(json_encode(['entity_type_id' => $typeId], JSON_THROW_ON_ERROR));

        $entityResponse = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities')->withBody($bodyEntity),
        );
        self::assertSame(201, $entityResponse->getStatusCode());
        $createdEntity = $this->decodeJson($entityResponse);
        $entityId = (int) $createdEntity['id'];

        $bodyTf = $this->factory->createStream(json_encode([
            'entity_id' => $entityId,
            'field_key' => 'title',
            'value' => true,
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/bool-fields')->withBody($bodyTf),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(201, $response->getStatusCode());
        self::assertStringStartsWith('/api/v1/bool-fields/', $response->getHeaderLine('Location'));
        self::assertSame($entityId, $payload['entity_id']);
        self::assertSame('title', $payload['field_key']);
        self::assertSame(true, $payload['value']);
    }

    public function testPostUnregisteredFieldKeyReturns422(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Item', slug: 'item'));
        $bodyEntity = $this->factory->createStream(json_encode(['entity_type_id' => $typeId], JSON_THROW_ON_ERROR));

        $entityResponse = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities')->withBody($bodyEntity),
        );
        $entityId = (int) $this->decodeJson($entityResponse)['id'];

        $bodyTf = $this->factory->createStream(json_encode([
            'entity_id' => $entityId,
            'field_key' => 'title',
            'value' => true,
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/bool-fields')->withBody($bodyTf),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(422, $response->getStatusCode());
        self::assertStringEndsWith('field-key-not-registered', (string) $payload['type']);
    }

    public function testPostMismatchedFieldTypeReturns422(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Item', slug: 'item'));
        $this->fieldDefs->save(new FieldDef(entityTypeId: $typeId, fieldKey: 'count', dataType: 'text'));

        $bodyEntity = $this->factory->createStream(json_encode(['entity_type_id' => $typeId], JSON_THROW_ON_ERROR));

        $entityResponse = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities')->withBody($bodyEntity),
        );
        $entityId = (int) $this->decodeJson($entityResponse)['id'];

        $bodyTf = $this->factory->createStream(json_encode([
            'entity_id' => $entityId,
            'field_key' => 'count',
            'value' => true,
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/bool-fields')->withBody($bodyTf),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(422, $response->getStatusCode());
        self::assertStringEndsWith('field-type-mismatch', (string) $payload['type']);
    }

    public function testAfterDeleteGetBoolFieldReturns404(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Item', slug: 'item'));
        $this->fieldDefs->save(new FieldDef(entityTypeId: $typeId, fieldKey: 'body', dataType: 'bool'));

        $bodyEntity = $this->factory->createStream(json_encode(['entity_type_id' => $typeId], JSON_THROW_ON_ERROR));

        $entityResponse = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities')->withBody($bodyEntity),
        );
        $entityId = (int) $this->decodeJson($entityResponse)['id'];

        $bodyTf = $this->factory->createStream(json_encode([
            'entity_id' => $entityId,
            'field_key' => 'body',
            'value' => true,
        ], JSON_THROW_ON_ERROR));

        $createTf = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/bool-fields')->withBody($bodyTf),
        );
        self::assertSame(201, $createTf->getStatusCode());
        $id = (int) $this->decodeJson($createTf)['id'];

        $getBefore = $this->application->handle(
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/bool-fields/{$id}"),
        );
        self::assertSame(200, $getBefore->getStatusCode());

        $del = $this->application->handle(
            $this->factory->createServerRequest('DELETE', "https://example.test/api/v1/bool-fields/{$id}"),
        );
        self::assertSame(204, $del->getStatusCode());

        $getAfter = $this->application->handle(
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/bool-fields/{$id}"),
        );
        $payload = $this->decodeJson($getAfter);

        self::assertSame(404, $getAfter->getStatusCode());
        self::assertStringEndsWith('not-found', (string) $payload['type']);
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
