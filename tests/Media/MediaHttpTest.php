<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Media;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use NeNeRecords\Media\DeleteMediaHandler;
use NeNeRecords\Media\DeleteMediaUseCase;
use NeNeRecords\Media\ListMediaHandler;
use NeNeRecords\Media\ListMediaUseCase;
use NeNeRecords\Media\Media;
use NeNeRecords\Media\MediaNotFoundExceptionHandler;
use NeNeRecords\Media\MediaRouteRegistrar;
use NeNeRecords\Media\ServeMediaHandler;
use NeNeRecords\Media\UploadMediaHandler;
use NeNeRecords\Media\UploadMediaUseCase;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MediaHttpTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryMediaRepository $repository;
    private string $storageRoot;
    private RequestHandlerInterface $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();
        $this->storageRoot = sys_get_temp_dir() . '/nene-media-test-' . bin2hex(random_bytes(4));
        mkdir($this->storageRoot, 0755, true);

        $this->repository = new InMemoryMediaRepository([
            new Media(
                id: 1,
                originalName: 'photo.jpg',
                storedName: 'abc123.jpg',
                mimeType: 'image/jpeg',
                size: 102400,
                url: '/media/2026/05/abc123.jpg',
                createdAt: '2026-05-20 10:00:00',
            ),
            new Media(
                id: 2,
                originalName: 'document.pdf',
                storedName: 'def456.pdf',
                mimeType: 'application/pdf',
                size: 512000,
                url: '/media/2026/05/def456.pdf',
                createdAt: '2026-05-21 12:00:00',
            ),
        ]);

        $jsonResponse = new JsonResponseFactory($this->factory, $this->factory);
        $problemDetails = new ProblemDetailsResponseFactory($this->factory, $this->factory);

        $registrar = new MediaRouteRegistrar(
            new UploadMediaHandler(
                new UploadMediaUseCase($this->repository, $this->storageRoot),
                $jsonResponse,
            ),
            new ListMediaHandler(
                new ListMediaUseCase($this->repository),
                $jsonResponse,
            ),
            new DeleteMediaHandler(
                new DeleteMediaUseCase($this->repository),
                $this->factory,
                $this->storageRoot,
            ),
            new ServeMediaHandler($this->storageRoot, $this->factory, $this->factory),
        );

        $this->application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [
                new MediaNotFoundExceptionHandler($problemDetails),
            ],
            routeRegistrars: [$registrar],
        ))->create();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up temp directory
        $this->rmdirRecursive($this->storageRoot);
    }

    // ── List ────────────────────────────────────────────────────────────────

    public function testGetMediaReturnsAllItems(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/media'),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertArrayHasKey('items', $payload);
        self::assertCount(2, $payload['items']);
    }

    public function testGetMediaItemsAreOrderedByCreatedAtDesc(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/media'),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        // document.pdf (2026-05-21) should come before photo.jpg (2026-05-20)
        self::assertSame('document.pdf', $payload['items'][0]['original_name']);
        self::assertSame('photo.jpg', $payload['items'][1]['original_name']);
    }

    public function testGetMediaItemHasExpectedFields(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/media'),
        );
        $payload = $this->decodeJson($response);

        $item = $payload['items'][0];
        self::assertArrayHasKey('id', $item);
        self::assertArrayHasKey('url', $item);
        self::assertArrayHasKey('original_name', $item);
        self::assertArrayHasKey('mime_type', $item);
        self::assertArrayHasKey('size', $item);
        self::assertArrayHasKey('created_at', $item);
    }

    public function testGetMediaReturnsEmptyListWhenNoItems(): void
    {
        $emptyRepo = new InMemoryMediaRepository([]);
        $jsonResponse = new JsonResponseFactory($this->factory, $this->factory);
        $problemDetails = new ProblemDetailsResponseFactory($this->factory, $this->factory);

        $registrar = new MediaRouteRegistrar(
            new UploadMediaHandler(new UploadMediaUseCase($emptyRepo, $this->storageRoot), $jsonResponse),
            new ListMediaHandler(new ListMediaUseCase($emptyRepo), $jsonResponse),
            new DeleteMediaHandler(new DeleteMediaUseCase($emptyRepo), $this->factory, $this->storageRoot),
            new ServeMediaHandler($this->storageRoot, $this->factory, $this->factory),
        );

        $app = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [new MediaNotFoundExceptionHandler($problemDetails)],
            routeRegistrars: [$registrar],
        ))->create();

        $response = $app->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/media'),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([], $payload['items']);
    }

    // ── Delete ───────────────────────────────────────────────────────────────

    public function testDeleteMediaReturns204(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('DELETE', 'https://example.test/api/v1/media/1'),
        );

        self::assertSame(204, $response->getStatusCode());
        self::assertNull($this->repository->findById(1));
    }

    public function testDeleteMediaRemovesFromList(): void
    {
        $this->application->handle(
            $this->factory->createServerRequest('DELETE', 'https://example.test/api/v1/media/1'),
        );

        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/media'),
        );
        $payload = $this->decodeJson($response);

        self::assertCount(1, $payload['items']);
        self::assertSame('document.pdf', $payload['items'][0]['original_name']);
    }

    public function testDeleteNonExistentMediaReturns404(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('DELETE', 'https://example.test/api/v1/media/999'),
        );

        self::assertSame(404, $response->getStatusCode());
    }

    public function testDeleteMediaDeletesPhysicalFile(): void
    {
        // Create a fake physical file
        $dir = $this->storageRoot . '/2026/05';
        mkdir($dir, 0755, true);
        $filePath = $dir . '/abc123.jpg';
        file_put_contents($filePath, 'fake image data');
        self::assertFileExists($filePath);

        $this->application->handle(
            $this->factory->createServerRequest('DELETE', 'https://example.test/api/v1/media/1'),
        );

        self::assertFileDoesNotExist($filePath);
    }

    /** @return array<string, mixed> */
    private function decodeJson(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data)) {
            return [];
        }

        return $data;
    }

    private function rmdirRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = scandir($dir);

        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $dir . '/' . $file;

            if (is_dir($path)) {
                $this->rmdirRecursive($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
