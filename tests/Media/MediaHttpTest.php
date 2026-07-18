<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Media;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use Nene2\Http\UtcClock;
use Nene2\Routing\Router;
use NeNeRecords\Media\DeleteMediaHandler;
use NeNeRecords\Media\DeleteMediaUseCase;
use NeNeRecords\Media\FindMediaUsagesUseCase;
use NeNeRecords\Media\GdImageProcessor;
use NeNeRecords\Media\ImageProcessorInterface;
use NeNeRecords\Media\ListMediaHandler;
use NeNeRecords\Media\ListMediaUsagesHandler;
use NeNeRecords\Media\ListMediaUseCase;
use NeNeRecords\Media\LocalStorage;
use NeNeRecords\Media\Media;
use NeNeRecords\Media\MediaInUseExceptionHandler;
use NeNeRecords\Media\MediaNotFoundExceptionHandler;
use NeNeRecords\Media\MediaRouteRegistrar;
use NeNeRecords\Media\MediaUsage;
use NeNeRecords\Media\ServeDerivativeHandler;
use NeNeRecords\Media\ServeMediaHandler;
use NeNeRecords\Media\UpdateMediaAltHandler;
use NeNeRecords\Media\UpdateMediaAltUseCase;
use NeNeRecords\Media\UploadMediaHandler;
use NeNeRecords\Media\UploadMediaUseCase;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;

final class MediaHttpTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryMediaRepository $repository;
    private string $storageRoot;
    private LocalStorage $storage;
    private RequestHandlerInterface $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();
        $this->storageRoot = sys_get_temp_dir() . '/nene-media-test-' . bin2hex(random_bytes(4));
        mkdir($this->storageRoot, 0755, true);
        $this->storage = new LocalStorage($this->storageRoot);

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
                new UploadMediaUseCase($this->repository, $this->storage, new UtcClock()),
                $jsonResponse,
            ),
            new ListMediaHandler(
                new ListMediaUseCase($this->repository),
                $jsonResponse,
            ),
            new DeleteMediaHandler(
                new DeleteMediaUseCase($this->repository, $this->storage),
                $this->factory,
            ),
            new ServeMediaHandler($this->storage, $this->factory, $this->factory),
            new UpdateMediaAltHandler(
                new UpdateMediaAltUseCase($this->repository),
                $jsonResponse,
            ),
            new ServeDerivativeHandler($this->storage, new GdImageProcessor(), $this->factory, $this->factory, new NullLogger()),
            new ListMediaUsagesHandler(
                new FindMediaUsagesUseCase($this->repository),
                $jsonResponse,
            ),
        );

        $this->application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [
                new MediaNotFoundExceptionHandler($problemDetails),
                new MediaInUseExceptionHandler($problemDetails),
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
            new UploadMediaHandler(new UploadMediaUseCase($emptyRepo, $this->storage, new UtcClock()), $jsonResponse),
            new ListMediaHandler(new ListMediaUseCase($emptyRepo), $jsonResponse),
            new DeleteMediaHandler(new DeleteMediaUseCase($emptyRepo, $this->storage), $this->factory),
            new ServeMediaHandler($this->storage, $this->factory, $this->factory),
            new UpdateMediaAltHandler(new UpdateMediaAltUseCase($emptyRepo), $jsonResponse),
            new ServeDerivativeHandler($this->storage, new GdImageProcessor(), $this->factory, $this->factory, new NullLogger()),
            new ListMediaUsagesHandler(new FindMediaUsagesUseCase($emptyRepo), $jsonResponse),
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

    // ── Usages (reverse-lookup) ──────────────────────────────────────────────

    public function testGetMediaUsagesReturnsReferencingEntities(): void
    {
        $this->repository->setUsages('/media/2026/05/abc123.jpg', [
            new MediaUsage(
                entityId: 7,
                entityTypeSlug: 'post',
                entitySlug: 'hello-world',
                status: 'published',
                fieldKey: 'cover',
                title: 'Hello World',
            ),
        ]);

        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/media/1/usages'),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(1, $payload['items']);
        self::assertSame(7, $payload['items'][0]['entity_id']);
        self::assertSame('post', $payload['items'][0]['entity_type_slug']);
        self::assertSame('cover', $payload['items'][0]['field_key']);
        self::assertSame('Hello World', $payload['items'][0]['title']);
    }

    public function testGetMediaUsagesReturnsEmptyWhenUnused(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/media/1/usages'),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([], $payload['items']);
    }

    public function testGetUsagesForMissingMediaReturns404(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/media/999/usages'),
        );

        self::assertSame(404, $response->getStatusCode());
    }

    public function testDeleteInUseMediaReturns409WithUsages(): void
    {
        $this->repository->setUsages('/media/2026/05/abc123.jpg', [
            new MediaUsage(
                entityId: 7,
                entityTypeSlug: 'post',
                entitySlug: 'hello-world',
                status: 'published',
                fieldKey: 'cover',
                title: 'Hello World',
            ),
        ]);

        $response = $this->application->handle(
            $this->factory->createServerRequest('DELETE', 'https://example.test/api/v1/media/1'),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(409, $response->getStatusCode());
        self::assertSame('media-in-use', basename($payload['type']));
        self::assertCount(1, $payload['usages']);
        self::assertSame(7, $payload['usages'][0]['entity_id']);
        // The media row must survive a blocked delete.
        self::assertNotNull($this->repository->findById(1));
    }

    // ── Update alt text ───────────────────────────────────────────────────────

    public function testUpdateMediaAltSetsAltText(): void
    {
        $body = $this->factory->createStream(json_encode(['alt_text' => 'A red car'], JSON_THROW_ON_ERROR));
        $response = $this->application->handle(
            $this->factory->createServerRequest('PATCH', 'https://example.test/api/v1/media/1')->withBody($body),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('A red car', $payload['alt_text']);
        self::assertSame('A red car', $this->repository->findById(1)?->altText);
    }

    public function testUpdateMediaAltClearsAltTextWhenBlank(): void
    {
        $this->repository->updateAltText(1, 'existing');

        $body = $this->factory->createStream(json_encode(['alt_text' => '   '], JSON_THROW_ON_ERROR));
        $response = $this->application->handle(
            $this->factory->createServerRequest('PATCH', 'https://example.test/api/v1/media/1')->withBody($body),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertNull($payload['alt_text']);
        self::assertNull($this->repository->findById(1)?->altText);
    }

    public function testUpdateMediaAltOnMissingReturns404(): void
    {
        $body = $this->factory->createStream(json_encode(['alt_text' => 'x'], JSON_THROW_ON_ERROR));
        $response = $this->application->handle(
            $this->factory->createServerRequest('PATCH', 'https://example.test/api/v1/media/999')->withBody($body),
        );

        self::assertSame(404, $response->getStatusCode());
    }

    public function testListItemsExposeDimensionAndAltKeys(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/media'),
        );
        $item = $this->decodeJson($response)['items'][0];

        self::assertArrayHasKey('width', $item);
        self::assertArrayHasKey('height', $item);
        self::assertArrayHasKey('alt_text', $item);
    }

    // ── Derivatives ────────────────────────────────────────────────────────────

    // ── Serve security headers ───────────────────────────────────────────────

    public function testServeSvgSetsSecurityHardeningHeaders(): void
    {
        $this->storage->write(
            '2026/05/icon.svg',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1"/></svg>',
        );

        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/media/2026/05/icon.svg'),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('image/svg+xml', $response->getHeaderLine('Content-Type'));
        self::assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        self::assertStringContainsString("default-src 'none'", $response->getHeaderLine('Content-Security-Policy'));
    }

    public function testServeNonSvgKeepsBaselineCspAndNosniff(): void
    {
        $this->seedStorageImage('2026/05/pic.png', 4, 4);

        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/media/2026/05/pic.png'),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        // Non-SVG keeps the framework baseline CSP; only SVG gets the locked-down 'none'.
        self::assertStringNotContainsString("default-src 'none'", $response->getHeaderLine('Content-Security-Policy'));
    }

    // ── Derivatives ────────────────────────────────────────────────────────────

    public function testDerivativeGeneratesResizedImageAndCachesIt(): void
    {
        $this->seedStorageImage('2026/05/pic.png', 800, 400);

        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/media/sm/2026/05/pic.png'),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('image/png', $response->getHeaderLine('Content-Type'));

        $info = getimagesizefromstring((string) $response->getBody());
        self::assertNotFalse($info);
        self::assertSame(320, $info[0], 'sm preset constrains width to 320');
        self::assertSame(160, $info[1]);

        self::assertFileExists($this->storageRoot . '/derivatives/sm/png/2026/05/pic.png');
    }

    public function testDerivativeCacheWriteFailureServesUncachedImageAndLogs(): void
    {
        // #949: var/media が web ユーザ非書き込みのとき、200 + text/html（mkdir の
        // PHP Warning 本文）で画像だけ静かに壊れていた。派生自体は生成済みなので、
        // キャッシュ書き込み失敗は「未キャッシュのまま画像を返す＋warning ログ」に落とす。
        $this->seedStorageImage('2026/05/pic.png', 800, 400);
        // derivatives/ の場所を平ファイルで塞ぐ — root 実行でも mkdir が必ず失敗する。
        file_put_contents($this->storageRoot . '/derivatives', 'not a directory');

        $log = new class () extends AbstractLogger {
            /** @var list<array{level: string, message: string}> */
            public array $records = [];

            /** @param array<mixed> $context */
            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->records[] = ['level' => (string) (is_scalar($level) ? $level : ''), 'message' => (string) $message];
            }
        };
        $handler = new ServeDerivativeHandler(
            $this->storage,
            new GdImageProcessor(),
            $this->factory,
            $this->factory,
            $log,
        );

        $response = $handler->handle($this->derivativeRequest('sm', 'pic.png'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('image/png', $response->getHeaderLine('Content-Type'));
        $info = getimagesizefromstring((string) $response->getBody());
        self::assertNotFalse($info, 'the body is the resized image itself, not a PHP warning');
        self::assertSame(320, $info[0], 'sm preset still constrains width to 320');
        self::assertFileDoesNotExist($this->storageRoot . '/derivatives/sm/png/2026/05/pic.png');
        self::assertCount(1, $log->records);
        self::assertSame('warning', $log->records[0]['level']);
        self::assertStringContainsString('derivative cache write failed', $log->records[0]['message']);
    }

    public function testDerivativeNegotiatesWebpFromAcceptHeader(): void
    {
        $this->seedStorageImage('2026/05/pic.png', 200, 200);

        $response = $this->application->handle(
            $this->factory
                ->createServerRequest('GET', 'https://example.test/media/thumb/2026/05/pic.png')
                ->withHeader('Accept', 'image/avif,image/webp,image/*'),
        );

        self::assertSame(200, $response->getStatusCode());
        // libavif is not guaranteed in CI; AVIF is preferred but at least webp/avif, not png.
        self::assertContains($response->getHeaderLine('Content-Type'), ['image/avif', 'image/webp']);
    }

    public function testDerivativeWithFormatOverrideReturnsWebp(): void
    {
        $this->seedStorageImage('2026/05/pic.png', 200, 200);

        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/media/thumb/2026/05/pic.png?fm=webp'),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('image/webp', $response->getHeaderLine('Content-Type'));
    }

    public function testDerivativeWithUnknownPresetReturns404(): void
    {
        $this->seedStorageImage('2026/05/pic.png', 200, 200);

        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/media/huge/2026/05/pic.png'),
        );

        self::assertSame(404, $response->getStatusCode());
    }

    public function testDerivativeForMissingOriginalReturns404(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/media/sm/2026/05/nope.png'),
        );

        self::assertSame(404, $response->getStatusCode());
    }

    public function testDerivativeFallsBackToWebpWhenAvifEncoderIsUnavailable(): void
    {
        // GD の AVIF 無しビルド（共有ホスティングで実在）では、Accept: image/avif を
        // 受けても encode 可能な形式へフォールバックしなければならない（#737）。
        $this->seedStorageImage('2026/05/pic.png', 200, 200);

        $response = $this->avifLessDerivativeHandler()->handle(
            $this->derivativeRequest('thumb', 'pic.png')
                ->withHeader('Accept', 'image/avif,image/webp,image/*'),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('image/webp', $response->getHeaderLine('Content-Type'));
    }

    public function testDerivativeWithUnsupportedFormatOverrideFallsBack(): void
    {
        $this->seedStorageImage('2026/05/pic.png', 200, 200);

        $response = $this->avifLessDerivativeHandler()->handle(
            $this->derivativeRequest('thumb', 'pic.png', '?fm=avif'),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('image/png', $response->getHeaderLine('Content-Type'), 'fm=avif が使えない場合はソース形式へ');
    }

    public function testDerivativeForUndecodableSourceReturns404(): void
    {
        // A file with an image extension but non-image bytes must not 500.
        $dir = $this->storageRoot . '/2026/05';
        mkdir($dir, 0755, true);
        file_put_contents($dir . '/broken.png', 'not really a png');

        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/media/sm/2026/05/broken.png'),
        );

        self::assertSame(404, $response->getStatusCode());
    }

    /** AVIF encoder を持たない環境を模した ServeDerivativeHandler（#737 回帰用）。 */
    private function avifLessDerivativeHandler(): ServeDerivativeHandler
    {
        $processor = new class () implements ImageProcessorInterface {
            private GdImageProcessor $inner;

            public function __construct()
            {
                $this->inner = new GdImageProcessor();
            }

            public function supportsSource(string $mimeType): bool
            {
                return $this->inner->supportsSource($mimeType);
            }

            public function supportsOutput(string $format): bool
            {
                return $format !== self::FORMAT_AVIF && $this->inner->supportsOutput($format);
            }

            public function resize(string $sourceBytes, int $maxWidth, string $format): string
            {
                if ($format === self::FORMAT_AVIF) {
                    throw new \Error('Call to undefined function imageavif()');
                }

                return $this->inner->resize($sourceBytes, $maxWidth, $format);
            }
        };

        return new ServeDerivativeHandler($this->storage, $processor, $this->factory, $this->factory, new NullLogger());
    }

    private function derivativeRequest(string $preset, string $filename, string $query = ''): \Psr\Http\Message\ServerRequestInterface
    {
        return $this->factory
            ->createServerRequest('GET', "https://example.test/media/{$preset}/2026/05/{$filename}{$query}")
            ->withQueryParams($query === '' ? [] : ['fm' => substr($query, strlen('?fm='))])
            ->withAttribute(Router::PARAMETERS_ATTRIBUTE, [
                'preset' => $preset,
                'year' => '2026',
                'month' => '05',
                'filename' => $filename,
            ]);
    }

    /**
     * @param positive-int $width
     * @param positive-int $height
     */
    private function seedStorageImage(string $key, int $width, int $height): void
    {
        $image = imagecreatetruecolor($width, $height);
        self::assertNotFalse($image);
        imagefilledrectangle($image, 0, 0, $width, $height, (int) imagecolorallocate($image, 10, 120, 200));
        $dir = $this->storageRoot . '/' . dirname($key);
        mkdir($dir, 0755, true);
        imagepng($image, $this->storageRoot . '/' . $key);
        imagedestroy($image);
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
