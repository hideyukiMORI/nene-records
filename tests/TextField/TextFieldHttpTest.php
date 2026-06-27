<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\TextField;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use NeNeRecords\Entity\CreateEntityHandler;
use NeNeRecords\Entity\CreateEntityUseCase;
use NeNeRecords\Entity\DeleteEntityHandler;
use NeNeRecords\Entity\DeleteEntityUseCase;
use NeNeRecords\Entity\EntityNotFoundExceptionHandler;
use NeNeRecords\Entity\EntityRouteRegistrar;
use NeNeRecords\Entity\ExcerptResolver;
use NeNeRecords\Entity\ExportEntitiesHandler;
use NeNeRecords\Entity\GetEntityByIdHandler;
use NeNeRecords\Entity\GetEntityByIdUseCase;
use NeNeRecords\Entity\ListEntitiesHandler;
use NeNeRecords\Entity\ListEntitiesUseCase;
use NeNeRecords\Entity\ListEntityRevisionsHandler;
use NeNeRecords\Entity\ListEntityRevisionsUseCase;
use NeNeRecords\Entity\ProcessScheduledPublishHandler;
use NeNeRecords\Entity\ProcessScheduledPublishUseCase;
use NeNeRecords\Entity\ScheduleEntityHandler;
use NeNeRecords\Entity\ScheduleEntityUseCase;
use NeNeRecords\Entity\UnscheduleEntityHandler;
use NeNeRecords\Entity\UnscheduleEntityUseCase;
use NeNeRecords\Entity\UpdateEntityHandler;
use NeNeRecords\Entity\UpdateEntityUseCase;
use NeNeRecords\EntityType\EntityType;
use NeNeRecords\EntityType\EntityTypeNotFoundExceptionHandler;
use NeNeRecords\FieldDef\FieldDef;
use NeNeRecords\FieldDef\FieldKeyNotRegisteredExceptionHandler;
use NeNeRecords\FieldDef\FieldTypeMismatchExceptionHandler;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\EntityType\InMemoryEntityTypeRepository;
use NeNeRecords\Tests\FieldDef\InMemoryFieldDefRepository;
use NeNeRecords\Tests\Setting\InMemorySettingRepository;
use NeNeRecords\TextField\CreateTextFieldHandler;
use NeNeRecords\TextField\CreateTextFieldUseCase;
use NeNeRecords\TextField\DeleteTextFieldHandler;
use NeNeRecords\TextField\DeleteTextFieldUseCase;
use NeNeRecords\TextField\GetTextFieldByIdHandler;
use NeNeRecords\TextField\GetTextFieldByIdUseCase;
use NeNeRecords\TextField\ListTextFieldsHandler;
use NeNeRecords\TextField\ListTextFieldsUseCase;
use NeNeRecords\TextField\TextFieldNotFoundExceptionHandler;
use NeNeRecords\TextField\TextFieldRouteRegistrar;
use NeNeRecords\TextField\UpdateTextFieldHandler;
use NeNeRecords\TextField\UpdateTextFieldUseCase;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class TextFieldHttpTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryEntityTypeRepository $entityTypes;
    private InMemoryEntityRepository $entities;
    private InMemoryFieldDefRepository $fieldDefs;
    private InMemoryTextFieldRepository $textFields;
    private RequestHandlerInterface $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();
        $this->entityTypes = new InMemoryEntityTypeRepository();
        $this->entities = new InMemoryEntityRepository();
        $this->fieldDefs = new InMemoryFieldDefRepository();
        $this->textFields = new InMemoryTextFieldRepository([], $this->entities);

        $jsonResponse = new JsonResponseFactory($this->factory, $this->factory);
        $problemDetails = new ProblemDetailsResponseFactory($this->factory, $this->factory);

        $entityRegistrar = new EntityRouteRegistrar(
            new GetEntityByIdHandler(new GetEntityByIdUseCase($this->entities), $jsonResponse),
            new CreateEntityHandler(new CreateEntityUseCase($this->entities, $this->entityTypes), $jsonResponse),
            new UpdateEntityHandler(new UpdateEntityUseCase($this->entities, $this->entityTypes), $jsonResponse),
            new DeleteEntityHandler(new DeleteEntityUseCase($this->entities), $this->factory),
            new \NeNeRecords\Entity\MoveEntityHandler(new \NeNeRecords\Entity\MoveEntitySubtreeUseCase($this->entities), $jsonResponse),
            new ListEntitiesHandler(new ListEntitiesUseCase($this->entities), $jsonResponse, new ExcerptResolver(new InMemoryTextFieldRepository(), new InMemorySettingRepository())),
            new ListEntityRevisionsHandler(new ListEntityRevisionsUseCase($this->entities), $jsonResponse),
            new ExportEntitiesHandler($this->entities, $this->textFields, $this->factory),
            new ScheduleEntityHandler(new ScheduleEntityUseCase($this->entities), $jsonResponse),
            new UnscheduleEntityHandler(new UnscheduleEntityUseCase($this->entities), $this->factory),
            new ProcessScheduledPublishHandler(new ProcessScheduledPublishUseCase($this->entities), $jsonResponse),
        );

        $textFieldRegistrar = new TextFieldRouteRegistrar(
            new ListTextFieldsHandler(new ListTextFieldsUseCase($this->textFields), $jsonResponse),
            new GetTextFieldByIdHandler(new GetTextFieldByIdUseCase($this->textFields), $jsonResponse),
            new CreateTextFieldHandler(new CreateTextFieldUseCase($this->textFields, $this->entities, $this->fieldDefs), $jsonResponse),
            new UpdateTextFieldHandler(new UpdateTextFieldUseCase($this->textFields, $this->entities, $this->fieldDefs), $jsonResponse),
            new DeleteTextFieldHandler(new DeleteTextFieldUseCase($this->textFields), $this->factory),
        );

        $this->application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [
                new EntityTypeNotFoundExceptionHandler($problemDetails),
                new EntityNotFoundExceptionHandler($problemDetails),
                new TextFieldNotFoundExceptionHandler($problemDetails),
                new FieldKeyNotRegisteredExceptionHandler($problemDetails),
                new FieldTypeMismatchExceptionHandler($problemDetails),
            ],
            routeRegistrars: [$entityRegistrar, $textFieldRegistrar],
        ))->create();
    }

    public function testPostTextFieldCreatesFieldAndReturns201WithLocation(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Item', slug: 'item'));
        $this->fieldDefs->save(new FieldDef(entityTypeId: $typeId, fieldKey: 'title', dataType: 'text'));

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
            'value' => 'Hello Field',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/text-fields')->withBody($bodyTf),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(201, $response->getStatusCode());
        self::assertStringStartsWith('/api/v1/text-fields/', $response->getHeaderLine('Location'));
        self::assertSame($entityId, $payload['entity_id']);
        self::assertSame('title', $payload['field_key']);
        self::assertSame('Hello Field', $payload['value']);
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
            'value' => 'Hello Field',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/text-fields')->withBody($bodyTf),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(422, $response->getStatusCode());
        self::assertStringEndsWith('field-key-not-registered', (string) $payload['type']);
    }

    public function testPostMismatchedFieldTypeReturns422(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Item', slug: 'item'));
        $this->fieldDefs->save(new FieldDef(entityTypeId: $typeId, fieldKey: 'count', dataType: 'int'));

        $bodyEntity = $this->factory->createStream(json_encode(['entity_type_id' => $typeId], JSON_THROW_ON_ERROR));

        $entityResponse = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities')->withBody($bodyEntity),
        );
        $entityId = (int) $this->decodeJson($entityResponse)['id'];

        $bodyTf = $this->factory->createStream(json_encode([
            'entity_id' => $entityId,
            'field_key' => 'count',
            'value' => '1',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/text-fields')->withBody($bodyTf),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(422, $response->getStatusCode());
        self::assertStringEndsWith('field-type-mismatch', (string) $payload['type']);
    }

    public function testAfterDeleteGetTextFieldReturns404(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Item', slug: 'item'));
        $this->fieldDefs->save(new FieldDef(entityTypeId: $typeId, fieldKey: 'body', dataType: 'text'));

        $bodyEntity = $this->factory->createStream(json_encode(['entity_type_id' => $typeId], JSON_THROW_ON_ERROR));

        $entityResponse = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities')->withBody($bodyEntity),
        );
        $entityId = (int) $this->decodeJson($entityResponse)['id'];

        $bodyTf = $this->factory->createStream(json_encode([
            'entity_id' => $entityId,
            'field_key' => 'body',
            'value' => 'Text',
        ], JSON_THROW_ON_ERROR));

        $createTf = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/text-fields')->withBody($bodyTf),
        );
        self::assertSame(201, $createTf->getStatusCode());
        $id = (int) $this->decodeJson($createTf)['id'];

        $getBefore = $this->application->handle(
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/text-fields/{$id}"),
        );
        self::assertSame(200, $getBefore->getStatusCode());

        $del = $this->application->handle(
            $this->factory->createServerRequest('DELETE', "https://example.test/api/v1/text-fields/{$id}"),
        );
        self::assertSame(204, $del->getStatusCode());

        $getAfter = $this->application->handle(
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/text-fields/{$id}"),
        );
        $payload = $this->decodeJson($getAfter);

        self::assertSame(404, $getAfter->getStatusCode());
        self::assertStringEndsWith('not-found', (string) $payload['type']);
    }

    public function testListTextFieldsFiltersByEntityId(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Item', slug: 'item'));
        $this->fieldDefs->save(new FieldDef(entityTypeId: $typeId, fieldKey: 'title', dataType: 'text'));
        $this->fieldDefs->save(new FieldDef(entityTypeId: $typeId, fieldKey: 'body', dataType: 'text'));

        $bodyEntity = $this->factory->createStream(json_encode(['entity_type_id' => $typeId], JSON_THROW_ON_ERROR));

        $entityAResponse = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities')->withBody($bodyEntity),
        );
        $entityAId = (int) $this->decodeJson($entityAResponse)['id'];

        $entityBResponse = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities')->withBody(
                $this->factory->createStream(json_encode(['entity_type_id' => $typeId], JSON_THROW_ON_ERROR)),
            ),
        );
        $entityBId = (int) $this->decodeJson($entityBResponse)['id'];

        $bodyA = $this->factory->createStream(json_encode([
            'entity_id' => $entityAId,
            'field_key' => 'title',
            'value' => 'Entity A title',
        ], JSON_THROW_ON_ERROR));
        $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/text-fields')->withBody($bodyA),
        );

        $bodyB = $this->factory->createStream(json_encode([
            'entity_id' => $entityBId,
            'field_key' => 'body',
            'value' => 'Entity B body',
        ], JSON_THROW_ON_ERROR));
        $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/text-fields')->withBody($bodyB),
        );

        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/text-fields?entity_id={$entityAId}"),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(1, $payload['items']);
        self::assertSame($entityAId, $payload['items'][0]['entity_id']);
        self::assertSame('title', $payload['items'][0]['field_key']);
        self::assertSame('Entity A title', $payload['items'][0]['value']);
    }

    public function testListTextFieldsFiltersByEntityTypeId(): void
    {
        $typeAId = $this->entityTypes->save(new EntityType(name: 'Type A', slug: 'type-a'));
        $typeBId = $this->entityTypes->save(new EntityType(name: 'Type B', slug: 'type-b'));
        $this->fieldDefs->save(new FieldDef(entityTypeId: $typeAId, fieldKey: 'title', dataType: 'text'));
        $this->fieldDefs->save(new FieldDef(entityTypeId: $typeBId, fieldKey: 'title', dataType: 'text'));

        $entityAId = $this->createEntity($typeAId);
        $entityBId = $this->createEntity($typeBId);

        $this->createTextField($entityAId, 'title', 'Type A title');
        $this->createTextField($entityBId, 'title', 'Type B title');

        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/text-fields?entity_type_id={$typeAId}"),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(1, $payload['items']);
        self::assertSame($entityAId, $payload['items'][0]['entity_id']);
        self::assertSame('Type A title', $payload['items'][0]['value']);
    }

    public function testCreateTextFieldWithLocaleStoresLocale(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Article', slug: 'article'));
        $this->fieldDefs->save(new FieldDef(entityTypeId: $typeId, fieldKey: 'title', dataType: 'text'));
        $entityId = $this->createEntity($typeId);

        $body = $this->factory->createStream(json_encode([
            'entity_id' => $entityId,
            'field_key' => 'title',
            'value' => 'こんにちは',
            'locale' => 'ja',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/text-fields')->withBody($body),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame('ja', $payload['locale']);
    }

    public function testListTextFieldsFiltersByLocale(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Article', slug: 'article'));
        $this->fieldDefs->save(new FieldDef(entityTypeId: $typeId, fieldKey: 'title', dataType: 'text'));
        $entityId = $this->createEntity($typeId);

        // Create default (null locale) and Japanese versions
        $this->createTextField($entityId, 'title', 'Hello');

        $bodyJa = $this->factory->createStream(json_encode([
            'entity_id' => $entityId,
            'field_key' => 'title',
            'value' => 'こんにちは',
            'locale' => 'ja',
        ], JSON_THROW_ON_ERROR));
        $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/text-fields')->withBody($bodyJa),
        );

        // Filter by locale=ja → should return only the Japanese version
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/text-fields?entity_id={$entityId}&locale=ja"),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(1, $payload['items']);
        self::assertSame('こんにちは', $payload['items'][0]['value']);
        self::assertSame('ja', $payload['items'][0]['locale']);
    }

    public function testResponseIncludesLocaleField(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Article', slug: 'article'));
        $this->fieldDefs->save(new FieldDef(entityTypeId: $typeId, fieldKey: 'body', dataType: 'text'));
        $entityId = $this->createEntity($typeId);

        $body = $this->factory->createStream(json_encode([
            'entity_id' => $entityId,
            'field_key' => 'body',
            'value' => 'Content',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/text-fields')->withBody($body),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(201, $response->getStatusCode());
        self::assertArrayHasKey('locale', $payload);
        self::assertNull($payload['locale']);
    }

    private function createEntity(int $entityTypeId): int
    {
        $bodyEntity = $this->factory->createStream(json_encode(['entity_type_id' => $entityTypeId], JSON_THROW_ON_ERROR));
        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities')->withBody($bodyEntity),
        );

        return (int) $this->decodeJson($response)['id'];
    }

    private function createTextField(int $entityId, string $fieldKey, string $value): void
    {
        $body = $this->factory->createStream(json_encode([
            'entity_id' => $entityId,
            'field_key' => $fieldKey,
            'value' => $value,
        ], JSON_THROW_ON_ERROR));

        $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/text-fields')->withBody($body),
        );
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
