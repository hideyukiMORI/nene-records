<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\EntityTag;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityNotFoundExceptionHandler;
use NeNeRecords\EntityTag\AttachEntityTagHandler;
use NeNeRecords\EntityTag\AttachEntityTagUseCase;
use NeNeRecords\EntityTag\DetachEntityTagHandler;
use NeNeRecords\EntityTag\DetachEntityTagUseCase;
use NeNeRecords\EntityTag\EntityTagAlreadyAttachedExceptionHandler;
use NeNeRecords\EntityTag\EntityTagNotAttachedExceptionHandler;
use NeNeRecords\EntityTag\EntityTagRouteRegistrar;
use NeNeRecords\EntityTag\ListEntityTagsHandler;
use NeNeRecords\EntityTag\ListEntityTagsUseCase;
use NeNeRecords\Tag\Tag;
use NeNeRecords\Tag\TagNotFoundExceptionHandler;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\Tag\InMemoryTagRepository;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class EntityTagHttpTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryEntityRepository $entities;
    private InMemoryTagRepository $tags;
    private InMemoryEntityTagRepository $entityTags;
    private RequestHandlerInterface $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();
        $this->entities = new InMemoryEntityRepository();
        $this->tags = new InMemoryTagRepository();
        $this->entityTags = new InMemoryEntityTagRepository();

        $jsonResponse = new JsonResponseFactory($this->factory, $this->factory);
        $problemDetails = new ProblemDetailsResponseFactory($this->factory, $this->factory);

        $registrar = new EntityTagRouteRegistrar(
            new ListEntityTagsHandler(new ListEntityTagsUseCase($this->entities, $this->entityTags), $jsonResponse),
            new AttachEntityTagHandler(new AttachEntityTagUseCase($this->entities, $this->tags, $this->entityTags), $jsonResponse),
            new DetachEntityTagHandler(new DetachEntityTagUseCase($this->entities, $this->entityTags), $this->factory),
        );

        $this->application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [
                new EntityNotFoundExceptionHandler($problemDetails),
                new TagNotFoundExceptionHandler($problemDetails),
                new EntityTagAlreadyAttachedExceptionHandler($problemDetails),
                new EntityTagNotAttachedExceptionHandler($problemDetails),
            ],
            routeRegistrars: [$registrar],
        ))->create();
    }

    public function testAttachListAndDetachEntityTag(): void
    {
        $entityId = $this->entities->save(new Entity(id: null, entityTypeId: 1));
        $tagId = $this->tags->save(new Tag(slug: 'featured', name: 'Featured'));
        $this->entityTags->seedTag($tagId, 'featured', 'Featured');

        $attachBody = $this->factory->createStream(json_encode(['tag_id' => $tagId], JSON_THROW_ON_ERROR));
        $attachResponse = $this->application->handle(
            $this->factory->createServerRequest('POST', "https://example.test/api/v1/entities/{$entityId}/tags")->withBody($attachBody),
        );
        $attachPayload = $this->decodeJson($attachResponse);

        self::assertSame(201, $attachResponse->getStatusCode());
        self::assertSame('featured', $attachPayload['slug']);

        $listResponse = $this->application->handle(
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/entities/{$entityId}/tags"),
        );
        $listPayload = $this->decodeJson($listResponse);

        self::assertSame(200, $listResponse->getStatusCode());
        self::assertCount(1, $listPayload['items']);
        self::assertSame($tagId, $listPayload['items'][0]['id']);

        $detachResponse = $this->application->handle(
            $this->factory->createServerRequest('DELETE', "https://example.test/api/v1/entities/{$entityId}/tags/{$tagId}"),
        );

        self::assertSame(204, $detachResponse->getStatusCode());
    }

    public function testAttachDuplicateReturns409(): void
    {
        $entityId = $this->entities->save(new Entity(id: null, entityTypeId: 1));
        $tagId = $this->tags->save(new Tag(slug: 'dup', name: 'Dup'));
        $this->entityTags->seedTag($tagId, 'dup', 'Dup');
        $this->entityTags->attach($entityId, $tagId);

        $body = $this->factory->createStream(json_encode(['tag_id' => $tagId], JSON_THROW_ON_ERROR));
        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', "https://example.test/api/v1/entities/{$entityId}/tags")->withBody($body),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(409, $response->getStatusCode());
        self::assertStringEndsWith('conflict', (string) $payload['type']);
    }

    public function testDetachWhenNotAttachedReturns404(): void
    {
        $entityId = $this->entities->save(new Entity(id: null, entityTypeId: 1));

        $response = $this->application->handle(
            $this->factory->createServerRequest('DELETE', "https://example.test/api/v1/entities/{$entityId}/tags/99"),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(404, $response->getStatusCode());
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
