<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Entity;

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
use NeNeRecords\Tests\EntityType\InMemoryEntityTypeRepository;
use NeNeRecords\Tests\Setting\InMemorySettingRepository;
use NeNeRecords\Tests\TextField\InMemoryTextFieldRepository;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class EntityHttpTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryEntityTypeRepository $entityTypes;
    private InMemoryEntityRepository $entities;
    private RequestHandlerInterface $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();
        $this->entityTypes = new InMemoryEntityTypeRepository();
        $this->entities = new InMemoryEntityRepository();

        $jsonResponse = new JsonResponseFactory($this->factory, $this->factory);
        $problemDetails = new ProblemDetailsResponseFactory($this->factory, $this->factory);

        $createUseCase = new CreateEntityUseCase($this->entities, $this->entityTypes);

        $registrar = new EntityRouteRegistrar(
            new GetEntityByIdHandler(new GetEntityByIdUseCase($this->entities), $jsonResponse),
            new CreateEntityHandler($createUseCase, $jsonResponse),
            new UpdateEntityHandler(new UpdateEntityUseCase($this->entities, $this->entityTypes), $jsonResponse),
            new DeleteEntityHandler(new DeleteEntityUseCase($this->entities), $this->factory),
            new \NeNeRecords\Entity\MoveEntityHandler(new \NeNeRecords\Entity\MoveEntitySubtreeUseCase($this->entities), $jsonResponse),
            new \NeNeRecords\Entity\ReorderEntitiesHandler(new \NeNeRecords\Entity\ReorderEntitiesUseCase($this->entities), $jsonResponse),
            new ListEntitiesHandler(new ListEntitiesUseCase($this->entities), $jsonResponse, new ExcerptResolver(new InMemoryTextFieldRepository(), new InMemorySettingRepository())),
            new ListEntityRevisionsHandler(new ListEntityRevisionsUseCase($this->entities), $jsonResponse),
            new ExportEntitiesHandler($this->entities, new InMemoryTextFieldRepository(), $this->factory),
            new ScheduleEntityHandler(new ScheduleEntityUseCase($this->entities), $jsonResponse),
            new UnscheduleEntityHandler(new UnscheduleEntityUseCase($this->entities), $this->factory),
            new ProcessScheduledPublishHandler(new ProcessScheduledPublishUseCase($this->entities), $jsonResponse),
        );

        $this->application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [
                new EntityTypeNotFoundExceptionHandler($problemDetails),
                new EntityNotFoundExceptionHandler($problemDetails),
            ],
            routeRegistrars: [$registrar],
        ))->create();
    }

    public function testPostEntityCreatesEntityAndReturns201WithLocation(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Doc', slug: 'doc'));

        $body = $this->factory->createStream(json_encode(['entity_type_id' => $typeId], JSON_THROW_ON_ERROR));
        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities')->withBody($body),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(201, $response->getStatusCode());
        self::assertStringStartsWith('/api/v1/entities/', $response->getHeaderLine('Location'));
        self::assertSame($typeId, $payload['entity_type_id']);
        self::assertFalse($payload['is_deleted']);
        self::assertIsInt($payload['id']);
    }

    public function testPostEntityPersistsLayoutOverride(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Doc', slug: 'doc'));

        $body = $this->factory->createStream(json_encode([
            'entity_type_id' => $typeId,
            'layout' => 'bare',
        ], JSON_THROW_ON_ERROR));
        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities')->withBody($body),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame('bare', $payload['layout']);

        $get = $this->application->handle(
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/entities/{$payload['id']}"),
        );
        self::assertSame('bare', $this->decodeJson($get)['layout']);
    }

    public function testPostEntityRejectsUnknownLayoutWith422(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Doc', slug: 'doc'));

        $body = $this->factory->createStream(json_encode([
            'entity_type_id' => $typeId,
            'layout' => 'fancy',
        ], JSON_THROW_ON_ERROR));
        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities')->withBody($body),
        );

        self::assertSame(422, $response->getStatusCode());
    }

    public function testPublishingCustomLayoutWithoutMetaDescriptionReturns422(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Page', slug: 'page'));
        $created = $this->decodeJson($this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities')->withBody(
                $this->factory->createStream(json_encode(['entity_type_id' => $typeId], JSON_THROW_ON_ERROR)),
            ),
        ));
        $id = (int) $created['id'];

        $body = $this->factory->createStream(json_encode([
            'entity_type_id' => $typeId,
            'status' => 'published',
            'layout' => 'custom',
        ], JSON_THROW_ON_ERROR));
        $response = $this->application->handle(
            $this->factory->createServerRequest('PUT', "https://example.test/api/v1/entities/{$id}")->withBody($body),
        );

        self::assertSame(422, $response->getStatusCode());
    }

    public function testDraftCustomLayoutWithoutMetaDescriptionIsAllowed(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Page', slug: 'page'));
        $created = $this->decodeJson($this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities')->withBody(
                $this->factory->createStream(json_encode(['entity_type_id' => $typeId], JSON_THROW_ON_ERROR)),
            ),
        ));
        $id = (int) $created['id'];

        $body = $this->factory->createStream(json_encode([
            'entity_type_id' => $typeId,
            'status' => 'draft',
            'layout' => 'custom',
        ], JSON_THROW_ON_ERROR));
        $response = $this->application->handle(
            $this->factory->createServerRequest('PUT', "https://example.test/api/v1/entities/{$id}")->withBody($body),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('custom', $this->decodeJson($response)['layout']);
    }

    public function testUpdateCustomLayoutWithMetaDescriptionSucceeds(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Page', slug: 'page'));
        $created = $this->decodeJson($this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities')->withBody(
                $this->factory->createStream(json_encode(['entity_type_id' => $typeId], JSON_THROW_ON_ERROR)),
            ),
        ));
        $id = (int) $created['id'];

        $body = $this->factory->createStream(json_encode([
            'entity_type_id' => $typeId,
            'status' => 'draft',
            'layout' => 'custom',
            'meta_description' => 'A crawlable description of the page.',
        ], JSON_THROW_ON_ERROR));
        $response = $this->application->handle(
            $this->factory->createServerRequest('PUT', "https://example.test/api/v1/entities/{$id}")->withBody($body),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('custom', $payload['layout']);
    }

    public function testAfterDeleteGetEntityReturns404(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Thing', slug: 'thing'));

        $body = $this->factory->createStream(json_encode(['entity_type_id' => $typeId], JSON_THROW_ON_ERROR));
        $createResponse = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities')->withBody($body),
        );
        $created = $this->decodeJson($createResponse);
        $id = (int) $created['id'];

        self::assertSame(201, $createResponse->getStatusCode());

        $getBefore = $this->application->handle(
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/entities/{$id}"),
        );
        self::assertSame(200, $getBefore->getStatusCode());

        $del = $this->application->handle(
            $this->factory->createServerRequest('DELETE', "https://example.test/api/v1/entities/{$id}"),
        );
        self::assertSame(204, $del->getStatusCode());

        $getAfter = $this->application->handle(
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/entities/{$id}"),
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
