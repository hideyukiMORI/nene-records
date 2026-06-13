<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\NavigationItem;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use NeNeRecords\NavigationItem\CreateNavigationItemHandler;
use NeNeRecords\NavigationItem\CreateNavigationItemUseCase;
use NeNeRecords\NavigationItem\DeleteNavigationItemHandler;
use NeNeRecords\NavigationItem\DeleteNavigationItemUseCase;
use NeNeRecords\NavigationItem\ListNavigationItemsHandler;
use NeNeRecords\NavigationItem\ListNavigationItemsUseCase;
use NeNeRecords\NavigationItem\ListPublicNavigationItemsHandler;
use NeNeRecords\NavigationItem\NavigationItemNotFoundExceptionHandler;
use NeNeRecords\NavigationItem\NavigationItemRouteRegistrar;
use NeNeRecords\NavigationItem\UpdateNavigationItemHandler;
use NeNeRecords\NavigationItem\UpdateNavigationItemUseCase;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class NavigationItemHttpTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryNavigationItemRepository $repository;
    private RequestHandlerInterface $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();
        $this->repository = new InMemoryNavigationItemRepository();

        $jsonResponse = new JsonResponseFactory($this->factory, $this->factory);
        $problemDetails = new ProblemDetailsResponseFactory($this->factory, $this->factory);

        $useCase = new ListNavigationItemsUseCase($this->repository);

        $registrar = new NavigationItemRouteRegistrar(
            new ListNavigationItemsHandler($useCase, $jsonResponse),
            new ListPublicNavigationItemsHandler($useCase, $jsonResponse, $this->factory),
            new CreateNavigationItemHandler(new CreateNavigationItemUseCase($this->repository), $jsonResponse),
            new UpdateNavigationItemHandler(new UpdateNavigationItemUseCase($this->repository), $jsonResponse),
            new DeleteNavigationItemHandler(new DeleteNavigationItemUseCase($this->repository), $jsonResponse),
        );

        $this->application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [
                new NavigationItemNotFoundExceptionHandler($problemDetails),
            ],
            routeRegistrars: [$registrar],
        ))->create();
    }

    public function testListNavigationItemsReturnsEmptyInitially(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/navigation-items'),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([], $payload['items']);
    }

    public function testCreateNavigationItemReturns201(): void
    {
        $body = $this->factory->createStream(json_encode([
            'label' => 'Home',
            'url' => '/',
            'display_order' => 0,
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/navigation-items')
                ->withBody($body),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame('Home', $payload['label']);
        self::assertSame('/', $payload['url']);
        self::assertSame('header', $payload['location']);
        self::assertSame(0, $payload['display_order']);
        self::assertIsInt($payload['id']);
    }

    public function testCreateNavigationItemAcceptsFooterLocation(): void
    {
        $body = $this->factory->createStream(json_encode([
            'label' => 'Privacy',
            'url' => '/privacy',
            'location' => 'footer',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/navigation-items')
                ->withBody($body),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame('footer', $payload['location']);
    }

    public function testCreateNavigationItemWithUnknownLocationReturns422(): void
    {
        $body = $this->factory->createStream(json_encode([
            'label' => 'Bad',
            'url' => '/bad',
            'location' => 'nowhere',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/navigation-items')
                ->withBody($body),
        );

        self::assertSame(422, $response->getStatusCode());
    }

    public function testCreateNavigationItemWithoutLabelReturns422(): void
    {
        $body = $this->factory->createStream(json_encode([
            'url' => '/',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/navigation-items')
                ->withBody($body),
        );

        self::assertSame(422, $response->getStatusCode());
    }

    public function testCreateNavigationItemWithoutUrlReturns422(): void
    {
        $body = $this->factory->createStream(json_encode([
            'label' => 'About',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/navigation-items')
                ->withBody($body),
        );

        self::assertSame(422, $response->getStatusCode());
    }

    public function testListNavigationItemsReturnsSortedByDisplayOrder(): void
    {
        foreach ([['About', '/about', 2], ['Home', '/', 0], ['Contact', '/contact', 1]] as [$label, $url, $order]) {
            $body = $this->factory->createStream(json_encode([
                'label' => $label,
                'url' => $url,
                'display_order' => $order,
            ], JSON_THROW_ON_ERROR));
            $this->application->handle(
                $this->factory->createServerRequest('POST', 'https://example.test/api/v1/navigation-items')
                    ->withBody($body),
            );
        }

        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/navigation-items'),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(3, $payload['items']);
        self::assertSame(['Home', 'Contact', 'About'], array_column($payload['items'], 'label'));
    }

    public function testUpdateNavigationItemReturns200(): void
    {
        // Create
        $body = $this->factory->createStream(json_encode([
            'label' => 'Home',
            'url' => '/',
            'display_order' => 0,
        ], JSON_THROW_ON_ERROR));
        $createResponse = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/navigation-items')
                ->withBody($body),
        );
        $created = $this->decodeJson($createResponse);
        $id = $created['id'];

        // Update
        $updateBody = $this->factory->createStream(json_encode([
            'label' => 'Home Updated',
            'url' => '/home',
            'display_order' => 1,
        ], JSON_THROW_ON_ERROR));
        $response = $this->application->handle(
            $this->factory->createServerRequest('PUT', "https://example.test/api/v1/navigation-items/{$id}")
                ->withBody($updateBody),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('Home Updated', $payload['label']);
        self::assertSame('/home', $payload['url']);
        self::assertSame(1, $payload['display_order']);
    }

    public function testUpdateNonExistentNavigationItemReturns404(): void
    {
        $body = $this->factory->createStream(json_encode([
            'label' => 'Missing',
            'url' => '/missing',
            'display_order' => 0,
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('PUT', 'https://example.test/api/v1/navigation-items/9999')
                ->withBody($body),
        );

        self::assertSame(404, $response->getStatusCode());
    }

    public function testDeleteNavigationItemReturns204(): void
    {
        // Create
        $body = $this->factory->createStream(json_encode([
            'label' => 'To Delete',
            'url' => '/delete-me',
            'display_order' => 0,
        ], JSON_THROW_ON_ERROR));
        $createResponse = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/navigation-items')
                ->withBody($body),
        );
        $created = $this->decodeJson($createResponse);
        $id = $created['id'];

        // Delete
        $response = $this->application->handle(
            $this->factory->createServerRequest('DELETE', "https://example.test/api/v1/navigation-items/{$id}"),
        );
        self::assertSame(204, $response->getStatusCode());

        // Verify gone
        $listResponse = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/navigation-items'),
        );
        $listPayload = $this->decodeJson($listResponse);
        self::assertCount(0, $listPayload['items']);
    }

    public function testDeleteNonExistentNavigationItemReturns404(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('DELETE', 'https://example.test/api/v1/navigation-items/9999'),
        );

        self::assertSame(404, $response->getStatusCode());
    }

    public function testPublicEndpointReturnsItems(): void
    {
        // Create an item first
        $body = $this->factory->createStream(json_encode([
            'label' => 'Public Link',
            'url' => '/public',
            'display_order' => 0,
        ], JSON_THROW_ON_ERROR));
        $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/navigation-items')
                ->withBody($body),
        );

        // Access public endpoint
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/public/navigation-items'),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(1, $payload['items']);
        self::assertSame('Public Link', $payload['items'][0]['label']);
    }

    public function testPublicEndpointFiltersByLocation(): void
    {
        foreach ([['Home', '/', 'header'], ['Privacy', '/privacy', 'footer']] as [$label, $url, $location]) {
            $body = $this->factory->createStream(json_encode([
                'label' => $label,
                'url' => $url,
                'location' => $location,
            ], JSON_THROW_ON_ERROR));
            $this->application->handle(
                $this->factory->createServerRequest('POST', 'https://example.test/api/v1/navigation-items')
                    ->withBody($body),
            );
        }

        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/public/navigation-items?location=footer'),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(1, $payload['items']);
        self::assertSame('Privacy', $payload['items'][0]['label']);
        self::assertSame('footer', $payload['items'][0]['location']);
    }

    /** @return array<string, mixed> */
    private function decodeJson(ResponseInterface $response): array
    {
        $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        return $payload;
    }
}
