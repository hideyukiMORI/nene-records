<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PreviewToken;

use DateTimeImmutable;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityNotFoundExceptionHandler;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\EntityType\EntityType;
use NeNeRecords\FieldDef\FieldDef;
use NeNeRecords\PreviewToken\EntityPreviewToken;
use NeNeRecords\PreviewToken\GeneratePreviewTokenHandler;
use NeNeRecords\PreviewToken\GeneratePreviewTokenUseCase;
use NeNeRecords\PreviewToken\GetPreviewRecordViewHandler;
use NeNeRecords\PreviewToken\GetPreviewRecordViewUseCase;
use NeNeRecords\PreviewToken\PreviewTokenNotFoundExceptionHandler;
use NeNeRecords\PreviewToken\PreviewTokenRouteRegistrar;
use NeNeRecords\PreviewToken\RevokePreviewTokenHandler;
use NeNeRecords\PreviewToken\RevokePreviewTokenUseCase;
use NeNeRecords\Setting\ListPublicSettingsUseCase;
use NeNeRecords\Tests\BoolField\InMemoryBoolFieldRepository;
use NeNeRecords\Tests\DateTimeField\InMemoryDateTimeFieldRepository;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\EntityRelation\InMemoryEntityRelationRepository;
use NeNeRecords\Tests\EntityType\InMemoryEntityTypeRepository;
use NeNeRecords\Tests\EnumField\InMemoryEnumFieldRepository;
use NeNeRecords\Tests\FieldDef\InMemoryFieldDefRepository;
use NeNeRecords\Tests\IntField\InMemoryIntFieldRepository;
use NeNeRecords\Tests\Setting\InMemorySettingRepository;
use NeNeRecords\Tests\TextField\InMemoryTextFieldRepository;
use NeNeRecords\TextField\TextField;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class PreviewTokenHttpTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryEntityRepository $entities;
    private InMemoryEntityPreviewTokenRepository $previewTokens;
    private RequestHandlerInterface $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();

        $entityTypes = new InMemoryEntityTypeRepository([
            new EntityType(name: 'Article', slug: 'article', id: 1),
        ]);
        $this->entities = new InMemoryEntityRepository([
            new Entity(id: 10, entityTypeId: 1, slug: 'draft-article', status: EntityStatus::Draft),
        ]);
        $this->previewTokens = new InMemoryEntityPreviewTokenRepository();

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'title', dataType: 'text', id: 1),
        ]);
        $textFields = new InMemoryTextFieldRepository([
            new TextField(entityId: 10, fieldKey: 'title', value: 'Draft Article', id: 1),
        ], $this->entities);

        $publicSettings = new ListPublicSettingsUseCase(new InMemorySettingRepository());

        $jsonResponse = new JsonResponseFactory($this->factory, $this->factory);
        $problemDetails = new ProblemDetailsResponseFactory($this->factory, $this->factory);

        $generateUseCase = new GeneratePreviewTokenUseCase($this->entities, $this->previewTokens);
        $revokeUseCase = new RevokePreviewTokenUseCase($this->entities, $this->previewTokens);
        $getPreviewUseCase = new GetPreviewRecordViewUseCase(
            $this->previewTokens,
            $entityTypes,
            $this->entities,
            $fieldDefs,
            $textFields,
            new InMemoryIntFieldRepository(),
            new InMemoryEnumFieldRepository(),
            new InMemoryBoolFieldRepository(),
            new InMemoryDateTimeFieldRepository(),
            new InMemoryEntityRelationRepository(),
            $publicSettings,
        );

        $registrar = new PreviewTokenRouteRegistrar(
            new GeneratePreviewTokenHandler($generateUseCase, $jsonResponse),
            new RevokePreviewTokenHandler($revokeUseCase, $this->factory),
            new GetPreviewRecordViewHandler($getPreviewUseCase, $jsonResponse),
        );

        $this->application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [
                new EntityNotFoundExceptionHandler($problemDetails),
                new PreviewTokenNotFoundExceptionHandler($problemDetails),
            ],
            routeRegistrars: [$registrar],
        ))->create();
    }

    public function testGeneratePreviewTokenReturns201(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities/10/preview-token'),
        );

        self::assertSame(201, $response->getStatusCode());

        $payload = $this->decodeJson($response);
        self::assertArrayHasKey('token', $payload);
        self::assertArrayHasKey('expires_at', $payload);
        self::assertArrayHasKey('preview_url', $payload);
        self::assertNotEmpty($payload['token']);
        self::assertStringStartsWith('/api/v1/public/preview/', $payload['preview_url']);
    }

    public function testGeneratePreviewTokenForNonExistentEntityReturns404(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities/999/preview-token'),
        );

        self::assertSame(404, $response->getStatusCode());
    }

    public function testGetPreviewRecordReturnsBootstrap(): void
    {
        // First generate a token
        $generateResponse = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities/10/preview-token'),
        );
        $generatePayload = $this->decodeJson($generateResponse);
        $token = $generatePayload['token'];

        // Then fetch the preview record
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/public/preview/' . $token),
        );

        self::assertSame(200, $response->getStatusCode());

        $payload = $this->decodeJson($response);
        self::assertArrayHasKey('entity', $payload);
        self::assertArrayHasKey('entityTypes', $payload);
    }

    public function testGetPreviewRecordWorksForDraftEntity(): void
    {
        // Generate a token for a draft entity
        $generateResponse = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities/10/preview-token'),
        );
        $generatePayload = $this->decodeJson($generateResponse);
        $token = $generatePayload['token'];

        // Draft entity should be accessible via preview
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/public/preview/' . $token),
        );

        self::assertSame(200, $response->getStatusCode());
    }

    public function testGetPreviewRecordWithInvalidTokenReturns404(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/public/preview/invalid-token'),
        );

        self::assertSame(404, $response->getStatusCode());
    }

    public function testGetPreviewRecordWithExpiredTokenReturns404(): void
    {
        $expiredToken = new EntityPreviewToken(
            id: null,
            entityId: 10,
            token: 'expired-token-abc',
            expiresAt: new DateTimeImmutable('-1 hour'),
            createdAt: new DateTimeImmutable('-25 hours'),
        );
        $this->previewTokens->save($expiredToken);

        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/public/preview/expired-token-abc'),
        );

        self::assertSame(404, $response->getStatusCode());
    }

    public function testRevokePreviewTokenReturns204(): void
    {
        // Generate a token first
        $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities/10/preview-token'),
        );

        // Revoke it
        $response = $this->application->handle(
            $this->factory->createServerRequest('DELETE', 'https://example.test/api/v1/entities/10/preview-token'),
        );

        self::assertSame(204, $response->getStatusCode());
    }

    public function testRevokePreviewTokenForNonExistentEntityReturns404(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('DELETE', 'https://example.test/api/v1/entities/999/preview-token'),
        );

        self::assertSame(404, $response->getStatusCode());
    }

    public function testGeneratePreviewTokenRotatesExistingToken(): void
    {
        // Generate first token
        $first = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities/10/preview-token'),
        );
        $firstToken = $this->decodeJson($first)['token'];

        // Generate second token (should revoke first)
        $second = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities/10/preview-token'),
        );
        $secondToken = $this->decodeJson($second)['token'];

        // First token should no longer work
        $oldResponse = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/public/preview/' . $firstToken),
        );
        self::assertSame(404, $oldResponse->getStatusCode());

        // Second token should work
        $newResponse = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/public/preview/' . $secondToken),
        );
        self::assertSame(200, $newResponse->getStatusCode());
    }

    /** @return array<string, mixed> */
    private function decodeJson(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($data);

        return $data;
    }
}
