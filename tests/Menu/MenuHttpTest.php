<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Menu;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use NeNeRecords\Menu\CreateMenuHandler;
use NeNeRecords\Menu\CreateMenuUseCase;
use NeNeRecords\Menu\DeleteMenuHandler;
use NeNeRecords\Menu\DeleteMenuUseCase;
use NeNeRecords\Menu\ListMenusHandler;
use NeNeRecords\Menu\ListMenusUseCase;
use NeNeRecords\Menu\ListPublicMenusHandler;
use NeNeRecords\Menu\MenuNotFoundExceptionHandler;
use NeNeRecords\Menu\MenuRouteRegistrar;
use NeNeRecords\Menu\UpdateMenuHandler;
use NeNeRecords\Menu\UpdateMenuUseCase;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MenuHttpTest extends TestCase
{
    private Psr17Factory $factory;
    private RequestHandlerInterface $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();
        $repository = new InMemoryMenuRepository();

        $jsonResponse = new JsonResponseFactory($this->factory, $this->factory);
        $problemDetails = new ProblemDetailsResponseFactory($this->factory, $this->factory);

        $registrar = new MenuRouteRegistrar(
            new ListMenusHandler(new ListMenusUseCase($repository), $jsonResponse),
            new ListPublicMenusHandler(new ListMenusUseCase($repository), $jsonResponse, $this->factory),
            new CreateMenuHandler(new CreateMenuUseCase($repository), $jsonResponse),
            new UpdateMenuHandler(new UpdateMenuUseCase($repository), $jsonResponse),
            new DeleteMenuHandler(new DeleteMenuUseCase($repository), $jsonResponse),
        );

        $this->application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [new MenuNotFoundExceptionHandler($problemDetails)],
            routeRegistrars: [$registrar],
        ))->create();
    }

    /** @param array<string, mixed> $body */
    private function post(array $body): ResponseInterface
    {
        return $this->application->handle(
            $this->factory
                ->createServerRequest('POST', 'https://example.test/api/v1/menus')
                ->withBody($this->factory->createStream(json_encode($body, JSON_THROW_ON_ERROR))),
        );
    }

    public function testCreateMenuDerivesSlugAndReturns201(): void
    {
        $response = $this->post(['name' => 'Main Nav', 'location' => 'header']);
        $payload = $this->decodeJson($response);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame('Main Nav', $payload['name']);
        self::assertSame('main-nav', $payload['slug']);
        self::assertSame('header', $payload['location']);
    }

    public function testCreateMenuDeduplicatesSlug(): void
    {
        $this->post(['name' => 'Links']);
        $second = $this->decodeJson($this->post(['name' => 'Links']));

        self::assertSame('links-2', $second['slug']);
    }

    public function testCreateMenuAllowsNullLocation(): void
    {
        $payload = $this->decodeJson($this->post(['name' => 'Categories']));
        self::assertNull($payload['location']);
    }

    public function testCreateMenuWithoutNameReturns422(): void
    {
        self::assertSame(422, $this->post(['location' => 'header'])->getStatusCode());
    }

    public function testCreateMenuWithUnknownLocationReturns422(): void
    {
        self::assertSame(422, $this->post(['name' => 'X', 'location' => 'nope'])->getStatusCode());
    }

    public function testListAndPublicListReturnMenus(): void
    {
        $this->post(['name' => 'One']);
        $this->post(['name' => 'Two']);

        foreach (['/api/v1/menus', '/api/v1/public/menus'] as $path) {
            $payload = $this->decodeJson(
                $this->application->handle($this->factory->createServerRequest('GET', "https://example.test{$path}")),
            );
            self::assertCount(2, $payload['items'], $path);
        }
    }

    public function testUpdateMenu(): void
    {
        $created = $this->decodeJson($this->post(['name' => 'Old']));
        $id = $created['id'];

        $response = $this->application->handle(
            $this->factory
                ->createServerRequest('PUT', "https://example.test/api/v1/menus/{$id}")
                ->withBody($this->factory->createStream(
                    json_encode(['name' => 'New', 'location' => 'footer'], JSON_THROW_ON_ERROR),
                )),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('New', $payload['name']);
        self::assertSame('footer', $payload['location']);
    }

    public function testUpdateMissingMenuReturns404(): void
    {
        $response = $this->application->handle(
            $this->factory
                ->createServerRequest('PUT', 'https://example.test/api/v1/menus/999')
                ->withBody($this->factory->createStream(json_encode(['name' => 'X'], JSON_THROW_ON_ERROR))),
        );
        self::assertSame(404, $response->getStatusCode());
    }

    public function testDeleteMenuReturns204(): void
    {
        $created = $this->decodeJson($this->post(['name' => 'Temp']));
        $id = $created['id'];

        $response = $this->application->handle(
            $this->factory->createServerRequest('DELETE', "https://example.test/api/v1/menus/{$id}"),
        );
        self::assertSame(204, $response->getStatusCode());
    }

    /** @return array<string, mixed> */
    private function decodeJson(ResponseInterface $response): array
    {
        $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        return $payload;
    }
}
