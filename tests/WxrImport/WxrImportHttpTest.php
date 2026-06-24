<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\WxrImport;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\EntityTag\InMemoryEntityTagRepository;
use NeNeRecords\Tests\EntityType\InMemoryEntityTypeRepository;
use NeNeRecords\Tests\FieldDef\InMemoryFieldDefRepository;
use NeNeRecords\Tests\Tag\InMemoryTagRepository;
use NeNeRecords\Tests\TextField\InMemoryTextFieldRepository;
use NeNeRecords\Tests\UrlRedirect\InMemoryUrlRedirectRepository;
use NeNeRecords\WxrImport\WxrImportExecutor;
use NeNeRecords\WxrImport\WxrImportHttpHandler;
use NeNeRecords\WxrImport\WxrImportRouteRegistrar;
use NeNeRecords\WxrImport\WxrMediaFetchResult;
use NeNeRecords\WxrImport\WxrMediaImporter;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class WxrImportHttpTest extends TestCase
{
    private Psr17Factory $factory;
    private RequestHandlerInterface $application;
    private InMemoryEntityRepository $entities;
    private InMemoryEntityTypeRepository $entityTypes;
    private InMemoryTextFieldRepository $textFields;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new Psr17Factory();
        $this->entityTypes = new InMemoryEntityTypeRepository([]);
        $this->entities = new InMemoryEntityRepository([]);
        $this->textFields = new InMemoryTextFieldRepository([], $this->entities);
        $executor = new WxrImportExecutor(
            $this->entityTypes,
            new InMemoryFieldDefRepository([]),
            $this->entities,
            $this->textFields,
            new InMemoryTagRepository([]),
            new InMemoryEntityTagRepository(),
            new InMemoryUrlRedirectRepository(),
        );
        $mediaImporter = new WxrMediaImporter(
            new FakeWxrMediaFetcher([
                'https://old.example.com/wp-content/uploads/2024/01/image.jpg' => new WxrMediaFetchResult(
                    'binary-bytes',
                    'image/jpeg',
                    'image.jpg',
                ),
            ]),
            new FakeUploadMediaUseCase(),
        );
        $handler = new WxrImportHttpHandler(
            $executor,
            $mediaImporter,
            new JsonResponseFactory($this->factory, $this->factory),
            new ProblemDetailsResponseFactory($this->factory, $this->factory),
        );
        $this->application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            routeRegistrars: [new WxrImportRouteRegistrar($handler)],
        ))->create();
    }

    private function uploadRequest(?string $dryRun): ServerRequestInterface
    {
        $xml = file_get_contents(__DIR__ . '/fixtures/sample.wxr.xml');
        self::assertNotFalse($xml);
        $upload = $this->factory->createUploadedFile(
            $this->factory->createStream($xml),
            strlen($xml),
            UPLOAD_ERR_OK,
            'sample.wxr.xml',
            'application/xml',
        );
        $request = $this->factory
            ->createServerRequest('POST', 'https://example.test/api/v1/migration/wxr')
            ->withUploadedFiles(['file' => $upload]);

        return $dryRun === null ? $request : $request->withParsedBody(['dry_run' => $dryRun]);
    }

    /** @return array<string, mixed> */
    private function decode(ResponseInterface $response): array
    {
        return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    public function testPreviewReturnsPlanWithoutWriting(): void
    {
        $response = $this->application->handle($this->uploadRequest(null)); // default dry_run
        $payload = $this->decode($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('preview', $payload['mode']);
        self::assertSame(4, $payload['planned_count']);
        self::assertSame(1, $payload['skipped_count']);
        self::assertSame(['posts' => 3, 'pages' => 1], $payload['counts_by_entity_type']);
        // Nothing written in preview.
        self::assertNull($this->entityTypes->findBySlug('posts'));
    }

    public function testImportExecutesWhenDryRunFalse(): void
    {
        $response = $this->application->handle($this->uploadRequest('false'));
        $payload = $this->decode($response);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame('import', $payload['mode']);
        self::assertSame(4, $payload['created_entities']);
        self::assertSame(2, $payload['tags_ensured']);
        self::assertSame(2, $payload['redirects_created']);
        self::assertSame(1, $payload['media_imported']); // image.jpg fetched + stored
        self::assertSame(0, $payload['media_skipped']);

        // Entities actually created; body image URL rewritten to the new media URL.
        $posts = $this->entityTypes->findBySlug('posts');
        self::assertNotNull($posts?->id);
        $hello = $this->entities->findBySlug('hello-world', $posts->id);
        self::assertNotNull($hello?->id);

        $body = '';
        foreach ($this->textFields->findByEntityId($hello->id, 100, 0) as $tf) {
            if ($tf->fieldKey === 'body') {
                $body = $tf->value;
            }
        }
        self::assertStringContainsString('/media/imported/image.jpg', $body);
    }

    public function testReturns422WhenFileMissing(): void
    {
        $request = $this->factory->createServerRequest('POST', 'https://example.test/api/v1/migration/wxr');
        $response = $this->application->handle($request);

        self::assertSame(422, $response->getStatusCode());
    }

    public function testReturns422OnMalformedXml(): void
    {
        $upload = $this->factory->createUploadedFile(
            $this->factory->createStream('<rss><broken>'),
            13,
            UPLOAD_ERR_OK,
            'bad.xml',
            'application/xml',
        );
        $request = $this->factory
            ->createServerRequest('POST', 'https://example.test/api/v1/migration/wxr')
            ->withUploadedFiles(['file' => $upload]);

        self::assertSame(422, $this->application->handle($request)->getStatusCode());
    }
}
