<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Tag;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use NeNeRecords\Tag\CreateTagHandler;
use NeNeRecords\Tag\CreateTagUseCase;
use NeNeRecords\Tag\DeleteTagHandler;
use NeNeRecords\Tag\DeleteTagUseCase;
use NeNeRecords\Tag\GetTagByIdHandler;
use NeNeRecords\Tag\GetTagByIdUseCase;
use NeNeRecords\Tag\ListTagsHandler;
use NeNeRecords\Tag\ListTagsUseCase;
use NeNeRecords\Tag\Tag;
use NeNeRecords\Tag\TagNotFoundExceptionHandler;
use NeNeRecords\Tag\TagRouteRegistrar;
use NeNeRecords\Tag\TagSlugConflictExceptionHandler;
use NeNeRecords\Tag\UpdateTagHandler;
use NeNeRecords\Tag\UpdateTagUseCase;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class TagHttpTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryTagRepository $repository;
    private RequestHandlerInterface $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();
        $this->repository = new InMemoryTagRepository();

        $jsonResponse = new JsonResponseFactory($this->factory, $this->factory);
        $problemDetails = new ProblemDetailsResponseFactory($this->factory, $this->factory);

        $registrar = new TagRouteRegistrar(
            new GetTagByIdHandler(new GetTagByIdUseCase($this->repository), $jsonResponse),
            new CreateTagHandler(new CreateTagUseCase($this->repository), $jsonResponse),
            new UpdateTagHandler(new UpdateTagUseCase($this->repository), $jsonResponse),
            new DeleteTagHandler(new DeleteTagUseCase($this->repository), $this->factory),
            new ListTagsHandler(new ListTagsUseCase($this->repository), $jsonResponse),
        );

        $this->application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [
                new TagNotFoundExceptionHandler($problemDetails),
                new TagSlugConflictExceptionHandler($problemDetails),
            ],
            routeRegistrars: [$registrar],
        ))->create();
    }

    public function testPostTagCreatesTagAndReturns201WithLocation(): void
    {
        $body = $this->factory->createStream(json_encode(['name' => 'Featured', 'slug' => 'featured'], JSON_THROW_ON_ERROR));
        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/tags')->withBody($body),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(201, $response->getStatusCode());
        self::assertStringStartsWith('/api/v1/tags/', $response->getHeaderLine('Location'));
        self::assertSame('Featured', $payload['name']);
        self::assertSame('featured', $payload['slug']);
        self::assertIsInt($payload['id']);
    }

    public function testPostDuplicateSlugReturns409WithConflictProblemType(): void
    {
        $this->repository->save(new Tag(slug: 'dup-slug', name: 'Existing'));

        $body = $this->factory->createStream(json_encode(['name' => 'Try again', 'slug' => 'dup-slug'], JSON_THROW_ON_ERROR));
        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/tags')->withBody($body),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(409, $response->getStatusCode());
        self::assertStringEndsWith('conflict', (string) $payload['type']);
    }

    public function testGetTagByIdReturnsTag(): void
    {
        $id = $this->repository->save(new Tag(slug: 'news', name: 'News'));

        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/tags/{$id}"),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame($id, $payload['id']);
        self::assertSame('News', $payload['name']);
        self::assertSame('news', $payload['slug']);
    }

    public function testDeleteTagReturns204(): void
    {
        $id = $this->repository->save(new Tag(slug: 'temp', name: 'Temp'));

        $response = $this->application->handle(
            $this->factory->createServerRequest('DELETE', "https://example.test/api/v1/tags/{$id}"),
        );

        self::assertSame(204, $response->getStatusCode());
        self::assertNull($this->repository->findById($id));
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
