<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Setting;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use NeNeRecords\Setting\ListPublicSettingsHandler;
use NeNeRecords\Setting\ListPublicSettingsUseCase;
use NeNeRecords\Setting\ListSettingRevisionsHandler;
use NeNeRecords\Setting\ListSettingRevisionsUseCase;
use NeNeRecords\Setting\ListSettingsHandler;
use NeNeRecords\Setting\ListSettingsUseCase;
use NeNeRecords\Setting\SettingDef;
use NeNeRecords\Setting\SettingKeyNotFoundExceptionHandler;
use NeNeRecords\Setting\SettingRouteRegistrar;
use NeNeRecords\Setting\SettingValueInvalidExceptionHandler;
use NeNeRecords\Setting\UpdateSettingHandler;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\EntityType\InMemoryEntityTypeRepository;
use NeNeRecords\Tests\Media\InMemoryMediaRepository;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class SettingHttpTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemorySettingRepository $repository;
    private RequestHandlerInterface $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();
        $this->repository = new InMemorySettingRepository([
            new SettingDef(
                settingKey: 'site_name',
                dataType: 'text',
                defaultValue: 'NeNe Records',
                isPublic: true,
                label: 'Site name',
                id: 1,
            ),
            new SettingDef(
                settingKey: 'tagline',
                dataType: 'text',
                defaultValue: 'Demo tagline',
                isPublic: true,
                label: 'Tagline',
                id: 2,
            ),
        ]);

        $jsonResponse = new JsonResponseFactory($this->factory, $this->factory);
        $problemDetails = new ProblemDetailsResponseFactory($this->factory, $this->factory);

        $registrar = new SettingRouteRegistrar(
            new ListSettingsHandler(new ListSettingsUseCase($this->repository), $jsonResponse),
            new ListPublicSettingsHandler(new ListPublicSettingsUseCase($this->repository, new InMemoryMediaRepository(), new InMemoryEntityRepository(), new InMemoryEntityTypeRepository()), $jsonResponse, $this->factory),
            new UpdateSettingHandler(new InMemoryUpdateSettingUseCase($this->repository), $jsonResponse),
            new ListSettingRevisionsHandler(new ListSettingRevisionsUseCase($this->repository), $jsonResponse),
        );

        $this->application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [
                new SettingKeyNotFoundExceptionHandler($problemDetails),
                new SettingValueInvalidExceptionHandler($problemDetails),
            ],
            routeRegistrars: [$registrar],
        ))->create();
    }

    public function testListSettingsReturnsDefinitionsWithDefaults(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/settings'),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(2, $payload['items']);
        self::assertSame('site_name', $payload['items'][0]['setting_key']);
        self::assertSame('NeNe Records', $payload['items'][0]['value']);
    }

    public function testListPublicSettingsReturnsPublicKeysOnly(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/public/settings'),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(2, $payload['items']);
        self::assertSame(['site_name', 'tagline'], array_column($payload['items'], 'setting_key'));
    }

    public function testPutSettingUpdatesValueAndCreatesRevision(): void
    {
        $body = $this->factory->createStream(json_encode(['value' => 'My Site'], JSON_THROW_ON_ERROR));
        $response = $this->application->handle(
            $this->factory->createServerRequest('PUT', 'https://example.test/api/v1/settings/site_name')->withBody($body),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('My Site', $payload['value']);

        $revisions = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/settings/site_name/revisions'),
        );
        $revisionPayload = $this->decodeJson($revisions);

        self::assertSame('created', $revisionPayload['items'][0]['action']);
        self::assertSame('My Site', $revisionPayload['items'][0]['value']);
    }

    public function testPutUnknownSettingReturns404(): void
    {
        $body = $this->factory->createStream(json_encode(['value' => 'x'], JSON_THROW_ON_ERROR));
        $response = $this->application->handle(
            $this->factory->createServerRequest('PUT', 'https://example.test/api/v1/settings/missing_key')->withBody($body),
        );

        self::assertSame(404, $response->getStatusCode());
    }

    /** @return array<string, mixed> */
    private function decodeJson(ResponseInterface $response): array
    {
        $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        return $payload;
    }
}
