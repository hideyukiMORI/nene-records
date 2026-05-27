<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\EntityRelation;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityNotFoundExceptionHandler;
use NeNeRecords\EntityRelation\AttachEntityRelationHandler;
use NeNeRecords\EntityRelation\AttachEntityRelationUseCase;
use NeNeRecords\EntityRelation\DetachEntityRelationHandler;
use NeNeRecords\EntityRelation\DetachEntityRelationUseCase;
use NeNeRecords\EntityRelation\EntityRelationRouteRegistrar;
use NeNeRecords\EntityRelation\ListEntityRelationsHandler;
use NeNeRecords\EntityRelation\ListEntityRelationsUseCase;
use NeNeRecords\EntityRelation\RelationAlreadyAttachedExceptionHandler;
use NeNeRecords\EntityRelation\RelationNotAttachedExceptionHandler;
use NeNeRecords\EntityRelation\RelationTargetTypeMismatchExceptionHandler;
use NeNeRecords\FieldDef\FieldDef;
use NeNeRecords\FieldDef\FieldKeyNotRegisteredExceptionHandler;
use NeNeRecords\FieldDef\FieldTypeMismatchExceptionHandler;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\FieldDef\InMemoryFieldDefRepository;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class EntityRelationHttpTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryEntityRepository $entities;
    private InMemoryFieldDefRepository $fieldDefs;
    private InMemoryEntityRelationRepository $entityRelations;
    private RequestHandlerInterface $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();
        $this->entities = new InMemoryEntityRepository();
        $this->fieldDefs = new InMemoryFieldDefRepository();
        $this->entityRelations = new InMemoryEntityRelationRepository();

        $jsonResponse = new JsonResponseFactory($this->factory, $this->factory);
        $problemDetails = new ProblemDetailsResponseFactory($this->factory, $this->factory);

        $registrar = new EntityRelationRouteRegistrar(
            new ListEntityRelationsHandler(
                new ListEntityRelationsUseCase($this->entities, $this->fieldDefs, $this->entityRelations),
                $jsonResponse,
            ),
            new AttachEntityRelationHandler(
                new AttachEntityRelationUseCase($this->entities, $this->fieldDefs, $this->entityRelations),
                $jsonResponse,
            ),
            new DetachEntityRelationHandler(
                new DetachEntityRelationUseCase($this->entities, $this->entityRelations),
                $this->factory,
            ),
        );

        $this->application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [
                new EntityNotFoundExceptionHandler($problemDetails),
                new FieldKeyNotRegisteredExceptionHandler($problemDetails),
                new FieldTypeMismatchExceptionHandler($problemDetails),
                new RelationTargetTypeMismatchExceptionHandler($problemDetails),
                new RelationAlreadyAttachedExceptionHandler($problemDetails),
                new RelationNotAttachedExceptionHandler($problemDetails),
            ],
            routeRegistrars: [$registrar],
        ))->create();
    }

    public function testAttachListAndDetachEntityRelation(): void
    {
        $articleId = $this->entities->save(new Entity(id: null, entityTypeId: 1));
        $authorId = $this->entities->save(new Entity(id: null, entityTypeId: 2));
        $this->fieldDefs->save(new FieldDef(
            entityTypeId: 1,
            fieldKey: 'author',
            dataType: 'relation',
            targetEntityTypeId: 2,
            cardinality: 'one',
        ));

        $attachBody = $this->factory->createStream(json_encode([
            'field_key' => 'author',
            'target_entity_id' => $authorId,
        ], JSON_THROW_ON_ERROR));
        $attachResponse = $this->application->handle(
            $this->factory->createServerRequest('POST', "https://example.test/api/v1/entities/{$articleId}/relations")->withBody($attachBody),
        );
        $attachPayload = $this->decodeJson($attachResponse);

        self::assertSame(201, $attachResponse->getStatusCode());
        self::assertSame('author', $attachPayload['field_key']);
        self::assertSame($authorId, $attachPayload['target_entity_id']);

        $listResponse = $this->application->handle(
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/entities/{$articleId}/relations?field_key=author"),
        );
        $listPayload = $this->decodeJson($listResponse);

        self::assertSame(200, $listResponse->getStatusCode());
        self::assertCount(1, $listPayload['items']);
        self::assertSame($authorId, $listPayload['items'][0]['target_entity_id']);

        $detachResponse = $this->application->handle(
            $this->factory->createServerRequest('DELETE', "https://example.test/api/v1/entities/{$articleId}/relations/{$authorId}?field_key=author"),
        );

        self::assertSame(204, $detachResponse->getStatusCode());
    }

    public function testAttachDuplicateReturns409(): void
    {
        $articleId = $this->entities->save(new Entity(id: null, entityTypeId: 1));
        $authorId = $this->entities->save(new Entity(id: null, entityTypeId: 2));
        $this->fieldDefs->save(new FieldDef(
            entityTypeId: 1,
            fieldKey: 'author',
            dataType: 'relation',
            targetEntityTypeId: 2,
            cardinality: 'many',
        ));
        $this->entityRelations->attach($articleId, $authorId, 'author');

        $body = $this->factory->createStream(json_encode([
            'field_key' => 'author',
            'target_entity_id' => $authorId,
        ], JSON_THROW_ON_ERROR));
        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', "https://example.test/api/v1/entities/{$articleId}/relations")->withBody($body),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(409, $response->getStatusCode());
        self::assertStringEndsWith('relation-already-attached', (string) $payload['type']);
    }

    public function testAttachOneCardinalityReplacesExistingTarget(): void
    {
        $articleId = $this->entities->save(new Entity(id: null, entityTypeId: 1));
        $authorOneId = $this->entities->save(new Entity(id: null, entityTypeId: 2));
        $authorTwoId = $this->entities->save(new Entity(id: null, entityTypeId: 2));
        $this->fieldDefs->save(new FieldDef(
            entityTypeId: 1,
            fieldKey: 'author',
            dataType: 'relation',
            targetEntityTypeId: 2,
            cardinality: 'one',
        ));
        $this->entityRelations->attach($articleId, $authorOneId, 'author');

        $body = $this->factory->createStream(json_encode([
            'field_key' => 'author',
            'target_entity_id' => $authorTwoId,
        ], JSON_THROW_ON_ERROR));
        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', "https://example.test/api/v1/entities/{$articleId}/relations")->withBody($body),
        );

        self::assertSame(201, $response->getStatusCode());

        $listResponse = $this->application->handle(
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/entities/{$articleId}/relations?field_key=author"),
        );
        $listPayload = $this->decodeJson($listResponse);

        self::assertCount(1, $listPayload['items']);
        self::assertSame($authorTwoId, $listPayload['items'][0]['target_entity_id']);
    }

    public function testAttachWithWrongTargetTypeReturns422(): void
    {
        $articleId = $this->entities->save(new Entity(id: null, entityTypeId: 1));
        $wrongTargetId = $this->entities->save(new Entity(id: null, entityTypeId: 3));
        $this->fieldDefs->save(new FieldDef(
            entityTypeId: 1,
            fieldKey: 'author',
            dataType: 'relation',
            targetEntityTypeId: 2,
            cardinality: 'one',
        ));

        $body = $this->factory->createStream(json_encode([
            'field_key' => 'author',
            'target_entity_id' => $wrongTargetId,
        ], JSON_THROW_ON_ERROR));
        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', "https://example.test/api/v1/entities/{$articleId}/relations")->withBody($body),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(422, $response->getStatusCode());
        self::assertStringEndsWith('relation-target-type-mismatch', (string) $payload['type']);
    }

    public function testDetachWhenNotAttachedReturns404(): void
    {
        $articleId = $this->entities->save(new Entity(id: null, entityTypeId: 1));

        $response = $this->application->handle(
            $this->factory->createServerRequest('DELETE', "https://example.test/api/v1/entities/{$articleId}/relations/99?field_key=author"),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(404, $response->getStatusCode());
        self::assertStringEndsWith('relation-not-attached', (string) $payload['type']);
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
