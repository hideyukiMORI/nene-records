<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Entity;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use Nene2\Http\UtcClock;
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
            new UpdateEntityHandler(new UpdateEntityUseCase($this->entities, $this->entityTypes, new UtcClock()), $jsonResponse),
            new DeleteEntityHandler(new DeleteEntityUseCase($this->entities), $this->factory),
            new \NeNeRecords\Entity\MoveEntityHandler(new \NeNeRecords\Entity\MoveEntitySubtreeUseCase($this->entities), $jsonResponse),
            new \NeNeRecords\Entity\ReorderEntitiesHandler(new \NeNeRecords\Entity\ReorderEntitiesUseCase($this->entities), $jsonResponse),
            new ListEntitiesHandler(new ListEntitiesUseCase($this->entities, new UtcClock()), $jsonResponse, new ExcerptResolver(new InMemoryTextFieldRepository(), new InMemorySettingRepository())),
            new ListEntityRevisionsHandler(new ListEntityRevisionsUseCase($this->entities), $jsonResponse),
            new ExportEntitiesHandler($this->entities, new InMemoryTextFieldRepository(), $this->factory),
            new ScheduleEntityHandler(new ScheduleEntityUseCase($this->entities, new UtcClock()), $jsonResponse),
            new UnscheduleEntityHandler(new UnscheduleEntityUseCase($this->entities), $this->factory),
            new ProcessScheduledPublishHandler(new ProcessScheduledPublishUseCase($this->entities, new UtcClock()), $jsonResponse),
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
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/entities/{$payload['id']}")
                ->withAttribute('nene2.auth.claims', ['sub' => 'admin@example.test']),
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

    public function testPostEntityPersistsVisibilityOverrides(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Doc', slug: 'doc'));

        $body = $this->factory->createStream(json_encode([
            'entity_type_id' => $typeId,
            'show_comments' => false,
            'show_related' => true,
        ], JSON_THROW_ON_ERROR));
        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities')->withBody($body),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(201, $response->getStatusCode());
        self::assertFalse($payload['show_comments']);
        self::assertTrue($payload['show_related']);

        $get = $this->decodeJson($this->application->handle(
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/entities/{$payload['id']}")
                ->withAttribute('nene2.auth.claims', ['sub' => 'admin@example.test']),
        ));
        self::assertFalse($get['show_comments']);
        self::assertTrue($get['show_related']);
    }

    public function testPostEntityDefaultsVisibilityOverridesToNull(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Doc', slug: 'doc'));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities')->withBody(
                $this->factory->createStream(json_encode(['entity_type_id' => $typeId], JSON_THROW_ON_ERROR)),
            ),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(201, $response->getStatusCode());
        self::assertNull($payload['show_comments']);
        self::assertNull($payload['show_related']);
    }

    public function testPostEntityRejectsNonBooleanShowCommentsWith422(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Doc', slug: 'doc'));

        $body = $this->factory->createStream(json_encode([
            'entity_type_id' => $typeId,
            'show_comments' => 'yes',
        ], JSON_THROW_ON_ERROR));
        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities')->withBody($body),
        );

        self::assertSame(422, $response->getStatusCode());
    }

    public function testPutEntityUpdatesAndClearsVisibilityOverrides(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Doc', slug: 'doc'));
        $created = $this->decodeJson($this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities')->withBody(
                $this->factory->createStream(json_encode([
                    'entity_type_id' => $typeId,
                    'show_comments' => false,
                ], JSON_THROW_ON_ERROR)),
            ),
        ));
        $id = (int) $created['id'];

        // Flip the override the other way.
        $update = $this->decodeJson($this->application->handle(
            $this->factory->createServerRequest('PUT', "https://example.test/api/v1/entities/{$id}")->withBody(
                $this->factory->createStream(json_encode([
                    'entity_type_id' => $typeId,
                    'status' => 'draft',
                    'show_comments' => true,
                    'show_related' => false,
                ], JSON_THROW_ON_ERROR)),
            ),
        ));
        self::assertTrue($update['show_comments']);
        self::assertFalse($update['show_related']);

        // Omitting the fields clears them back to "follow the site setting".
        $cleared = $this->decodeJson($this->application->handle(
            $this->factory->createServerRequest('PUT', "https://example.test/api/v1/entities/{$id}")->withBody(
                $this->factory->createStream(json_encode([
                    'entity_type_id' => $typeId,
                    'status' => 'draft',
                ], JSON_THROW_ON_ERROR)),
            ),
        ));
        self::assertNull($cleared['show_comments']);
        self::assertNull($cleared['show_related']);
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
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/entities/{$id}")
                ->withAttribute('nene2.auth.claims', ['sub' => 'admin@example.test']),
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

    public function testAnonymousReadsAreScopedToPublishedRecords(): void
    {
        // #828: the open content-read surface must never surface drafts to anonymous
        // callers — the list hides them (even under ?status=draft) and get-by-id is
        // 404 — while an authenticated admin still sees everything.
        $typeId = $this->entityTypes->save(new EntityType(name: 'Doc', slug: 'doc'));

        $draftId = (int) $this->decodeJson($this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities')->withBody(
                $this->factory->createStream(json_encode(['entity_type_id' => $typeId, 'slug' => 'secret-draft'], JSON_THROW_ON_ERROR)),
            ),
        ))['id'];

        $publishedId = (int) $this->decodeJson($this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities')->withBody(
                $this->factory->createStream(json_encode(['entity_type_id' => $typeId, 'slug' => 'public-post', 'status' => 'published'], JSON_THROW_ON_ERROR)),
            ),
        ))['id'];

        $anonList = $this->decodeJson($this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/entities'),
        ));
        $anonIds = array_map(static fn (array $row): int => (int) $row['id'], $anonList['items']);
        self::assertContains($publishedId, $anonIds);
        self::assertNotContains($draftId, $anonIds);

        // `?status=draft` cannot bypass the anonymous published-only gate: the draft
        // never appears (published-only overrides the requested status filter).
        $anonDraftList = $this->decodeJson($this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/entities?status=draft'),
        ));
        $anonDraftIds = array_map(static fn (array $row): int => (int) $row['id'], $anonDraftList['items']);
        self::assertNotContains($draftId, $anonDraftIds);

        self::assertSame(200, $this->application->handle(
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/entities/{$publishedId}"),
        )->getStatusCode());
        self::assertSame(404, $this->application->handle(
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/entities/{$draftId}"),
        )->getStatusCode());

        // Authenticated admin still sees the draft by id.
        self::assertSame(200, $this->application->handle(
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/entities/{$draftId}")
                ->withAttribute('nene2.auth.claims', ['sub' => 'admin@example.test']),
        )->getStatusCode());
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
