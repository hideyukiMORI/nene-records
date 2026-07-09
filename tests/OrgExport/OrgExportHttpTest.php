<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\OrgExport;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use NeNeRecords\Organization\Organization;
use NeNeRecords\OrgExport\ExportOrganizationHandler;
use NeNeRecords\OrgExport\ImportOrganizationHandler;
use NeNeRecords\OrgExport\OrgExportRepositoryInterface;
use NeNeRecords\OrgExport\OrgExportRouteRegistrar;
use NeNeRecords\OrgExport\OrgImportRepositoryInterface;
use NeNeRecords\Tests\Organization\InMemoryOrganizationRepository;
use NeNeRecords\Tests\Support\FixedClock;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 実 Router 配線での export/import HTTP テスト。
 *
 * ルートパラメータは Router::PARAMETERS_ATTRIBUTE 経由でしか渡らないため、
 * ハンドラ単体に attribute を手置きするテストでは #739（{id} が常に 0 に落ちて
 * 404）の退行を検知できない。必ずアプリケーション（実 Router）越しに叩く。
 */
final class OrgExportHttpTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryOrganizationRepository $orgs;
    private RecordingOrgImportRepository $importRepository;
    private RequestHandlerInterface $application;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
        $this->orgs    = new InMemoryOrganizationRepository();
        $this->orgs->save(new Organization(name: 'Acme', slug: 'acme', plan: 'free', isActive: true));

        $exportRepository       = new StubOrgExportRepository();
        $this->importRepository = new RecordingOrgImportRepository();

        $jsonResponse   = new JsonResponseFactory($this->factory, $this->factory);
        $problemDetails = new ProblemDetailsResponseFactory($this->factory, $this->factory);

        $registrar = new OrgExportRouteRegistrar(
            new ExportOrganizationHandler($exportRepository, $this->orgs, $jsonResponse, $problemDetails, new FixedClock()),
            new ImportOrganizationHandler($this->importRepository, $this->orgs, $jsonResponse, $problemDetails),
        );

        $this->application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            routeRegistrars: [$registrar],
        ))->create();
    }

    public function testExportResolvesRouteIdAndReturnsPayload(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/superadmin/organizations/1/export'),
        );

        self::assertSame(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getBody(), true);
        self::assertIsArray($payload);
        self::assertSame(1, $payload['meta']['organization_id']);
        self::assertSame([['id' => 1, 'slug' => 'posts']], $payload['entity_types']);
    }

    public function testExportForUnknownOrgReturns404(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/superadmin/organizations/99/export'),
        );

        self::assertSame(404, $response->getStatusCode());
    }

    public function testImportResolvesRouteIdAndDelegatesPayload(): void
    {
        $body = $this->factory->createStream((string) json_encode([
            'meta'         => ['exported_at' => '2026-06-01T10:00:00+00:00', 'organization_id' => 5],
            'entity_types' => [['id' => 5, 'slug' => 'posts']],
        ]));

        $response = $this->application->handle(
            $this->factory
                ->createServerRequest('POST', 'https://example.test/api/v1/superadmin/organizations/1/import')
                ->withHeader('Content-Type', 'application/json')
                ->withBody($body),
        );

        self::assertSame(201, $response->getStatusCode());
        self::assertSame(1, $this->importRepository->lastTargetOrgId);

        $result = json_decode((string) $response->getBody(), true);
        self::assertIsArray($result);
        self::assertSame(1, $result['organization_id']);
        self::assertSame(['entity_types' => 1], $result['imported']);
    }

    public function testImportWithoutMetaReturns422(): void
    {
        $body = $this->factory->createStream((string) json_encode(['entity_types' => []]));

        $response = $this->application->handle(
            $this->factory
                ->createServerRequest('POST', 'https://example.test/api/v1/superadmin/organizations/1/import')
                ->withHeader('Content-Type', 'application/json')
                ->withBody($body),
        );

        self::assertSame(422, $response->getStatusCode());
    }
}

final class StubOrgExportRepository implements OrgExportRepositoryInterface
{
    /** @return list<array<string, mixed>> */
    public function findAllEntityTypes(int $orgId): array
    {
        return [['id' => 1, 'slug' => 'posts']];
    }

    /** @return list<array<string, mixed>> */
    public function findAllEntities(int $orgId): array
    {
        return [];
    }

    /** @return list<array<string, mixed>> */
    public function findAllFieldDefs(int $orgId): array
    {
        return [];
    }

    /** @return list<array<string, mixed>> */
    public function findAllTextFields(int $orgId): array
    {
        return [];
    }

    /** @return list<array<string, mixed>> */
    public function findAllIntFields(int $orgId): array
    {
        return [];
    }

    /** @return list<array<string, mixed>> */
    public function findAllEnumFields(int $orgId): array
    {
        return [];
    }

    /** @return list<array<string, mixed>> */
    public function findAllBoolFields(int $orgId): array
    {
        return [];
    }

    /** @return list<array<string, mixed>> */
    public function findAllDatetimeFields(int $orgId): array
    {
        return [];
    }

    /** @return list<array<string, mixed>> */
    public function findAllTags(int $orgId): array
    {
        return [];
    }

    /** @return list<array<string, mixed>> */
    public function findAllEntityTags(int $orgId): array
    {
        return [];
    }

    /** @return list<array<string, mixed>> */
    public function findAllNavigationItems(int $orgId): array
    {
        return [];
    }

    /** @return list<array<string, mixed>> */
    public function findAllSettingDefs(int $orgId): array
    {
        return [];
    }

    /** @return list<array<string, mixed>> */
    public function findAllSettingValues(int $orgId): array
    {
        return [];
    }

    /** @return list<array<string, mixed>> */
    public function findAllMedia(int $orgId): array
    {
        return [];
    }
}

final class RecordingOrgImportRepository implements OrgImportRepositoryInterface
{
    public ?int $lastTargetOrgId = null;

    /** @var array<string, mixed> */
    public array $lastPayload = [];

    public function import(int $targetOrgId, array $payload): array
    {
        $this->lastTargetOrgId = $targetOrgId;
        $this->lastPayload     = $payload;

        return ['entity_types' => count((array) ($payload['entity_types'] ?? []))];
    }
}
