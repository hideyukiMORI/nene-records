<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Widget;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use NeNeRecords\Widget\CreateWidgetHandler;
use NeNeRecords\Widget\CreateWidgetUseCase;
use NeNeRecords\Widget\DeleteWidgetHandler;
use NeNeRecords\Widget\DeleteWidgetUseCase;
use NeNeRecords\Widget\ListPublicWidgetsHandler;
use NeNeRecords\Widget\ListWidgetsHandler;
use NeNeRecords\Widget\ListWidgetsUseCase;
use NeNeRecords\Widget\UpdateWidgetHandler;
use NeNeRecords\Widget\UpdateWidgetUseCase;
use NeNeRecords\Widget\WidgetNotFoundExceptionHandler;
use NeNeRecords\Widget\WidgetRouteRegistrar;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class WidgetHttpTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryWidgetRepository $repository;
    private RequestHandlerInterface $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();
        $this->repository = new InMemoryWidgetRepository();

        $jsonResponse = new JsonResponseFactory($this->factory, $this->factory);
        $problemDetails = new ProblemDetailsResponseFactory($this->factory, $this->factory);

        $listUseCase = new ListWidgetsUseCase($this->repository);

        $registrar = new WidgetRouteRegistrar(
            new ListWidgetsHandler($listUseCase, $jsonResponse),
            new ListPublicWidgetsHandler($listUseCase, $jsonResponse, $this->factory),
            new CreateWidgetHandler(new CreateWidgetUseCase($this->repository), $jsonResponse),
            new UpdateWidgetHandler(new UpdateWidgetUseCase($this->repository), $jsonResponse),
            new DeleteWidgetHandler(new DeleteWidgetUseCase($this->repository), $jsonResponse),
        );

        $this->application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [new WidgetNotFoundExceptionHandler($problemDetails)],
            routeRegistrars: [$registrar],
        ))->create();
    }

    /** @param array<string, mixed> $body */
    private function post(array $body): ResponseInterface
    {
        return $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/widgets')
                ->withBody($this->factory->createStream(json_encode($body, JSON_THROW_ON_ERROR))),
        );
    }

    public function testCreateWidgetReturns201WithFields(): void
    {
        $response = $this->post([
            'widget_type' => 'recent-posts',
            'region' => 'sidebar',
            'display_order' => 1,
            'title' => 'Recent',
            'settings' => ['limit' => 5],
        ]);
        $payload = $this->decodeJson($response);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame('recent-posts', $payload['widget_type']);
        self::assertSame('sidebar', $payload['region']);
        self::assertSame('Recent', $payload['title']);
        self::assertSame(5, $payload['settings']['limit']);
    }

    public function testCreateRejectsUnknownWidgetType(): void
    {
        $response = $this->post(['widget_type' => 'magic', 'region' => 'sidebar']);
        self::assertSame(422, $response->getStatusCode());
    }

    public function testCreateRejectsUnknownRegion(): void
    {
        // `main` is record content, not a widget region.
        $response = $this->post(['widget_type' => 'recent-posts', 'region' => 'main']);
        self::assertSame(422, $response->getStatusCode());
    }

    public function testListAndPublicListReturnWidgets(): void
    {
        $this->post(['widget_type' => 'recent-posts', 'region' => 'sidebar', 'display_order' => 2]);
        $this->post(['widget_type' => 'recent-posts', 'region' => 'aside', 'display_order' => 1]);

        $list = $this->decodeJson($this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/widgets'),
        ));
        self::assertCount(2, $list['items']);

        $public = $this->decodeJson($this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/public/widgets'),
        ));
        self::assertCount(2, $public['items']);
    }

    public function testDeleteReturns204(): void
    {
        $id = (int) $this->decodeJson($this->post([
            'widget_type' => 'recent-posts',
            'region' => 'sidebar',
        ]))['id'];

        $response = $this->application->handle(
            $this->factory->createServerRequest('DELETE', "https://example.test/api/v1/widgets/{$id}"),
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertNull($this->repository->findById($id));
    }

    public function testUpdateMissingWidgetReturns404(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('PUT', 'https://example.test/api/v1/widgets/999')
                ->withBody($this->factory->createStream(json_encode([
                    'widget_type' => 'recent-posts',
                    'region' => 'sidebar',
                ], JSON_THROW_ON_ERROR))),
        );
        self::assertSame(404, $response->getStatusCode());
    }

    /** @return array<string, mixed> */
    private function decodeJson(ResponseInterface $response): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }
}
