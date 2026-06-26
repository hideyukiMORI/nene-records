<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Organization;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use NeNeRecords\Entitlement\UnlimitedEntitlementResolver;
use NeNeRecords\Organization\CreateOrganizationHandler;
use NeNeRecords\Organization\CreateOrganizationUseCase;
use NeNeRecords\Organization\DeleteOrganizationHandler;
use NeNeRecords\Organization\DeleteOrganizationUseCase;
use NeNeRecords\Organization\GetOrganizationByIdHandler;
use NeNeRecords\Organization\GetOrganizationByIdUseCase;
use NeNeRecords\Organization\ListOrganizationsHandler;
use NeNeRecords\Organization\ListOrganizationsUseCase;
use NeNeRecords\Organization\OrganizationNotFoundExceptionHandler;
use NeNeRecords\Organization\OrganizationRouteRegistrar;
use NeNeRecords\Organization\OrganizationSlugConflictExceptionHandler;
use NeNeRecords\Organization\UpdateOrganizationHandler;
use NeNeRecords\Organization\UpdateOrganizationUseCase;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class OrganizationHttpTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryOrganizationRepository $repository;
    private RequestHandlerInterface $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory    = new Psr17Factory();
        $this->repository = new InMemoryOrganizationRepository();

        $jsonResponse   = new JsonResponseFactory($this->factory, $this->factory);
        $problemDetails = new ProblemDetailsResponseFactory($this->factory, $this->factory);

        $registrar = new OrganizationRouteRegistrar(
            new ListOrganizationsHandler(new ListOrganizationsUseCase($this->repository), $jsonResponse),
            new GetOrganizationByIdHandler(new GetOrganizationByIdUseCase($this->repository), $jsonResponse),
            new CreateOrganizationHandler(new CreateOrganizationUseCase($this->repository, new RecordingDefaultContentTypeSeeder()), $jsonResponse),
            new UpdateOrganizationHandler(new UpdateOrganizationUseCase($this->repository, new UnlimitedEntitlementResolver()), $jsonResponse),
            new DeleteOrganizationHandler(new DeleteOrganizationUseCase($this->repository), $this->factory),
        );

        $this->application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [
                new OrganizationNotFoundExceptionHandler($problemDetails),
                new OrganizationSlugConflictExceptionHandler($problemDetails),
            ],
            routeRegistrars: [$registrar],
        ))->create();
    }

    // ── POST /api/v1/organizations ─────────────────────────────────────────────

    public function testPostOrganizationCreatesOrgAndReturns201(): void
    {
        $body = $this->factory->createStream(json_encode([
            'name' => 'Acme Corp',
            'slug' => 'acme',
            'plan' => 'pro',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/organizations')
                ->withBody($body),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(201, $response->getStatusCode());
        self::assertStringStartsWith('/api/v1/organizations/', $response->getHeaderLine('Location'));
        self::assertSame('Acme Corp', $payload['name']);
        self::assertSame('acme', $payload['slug']);
        self::assertSame('pro', $payload['plan']);
        self::assertTrue($payload['is_active']);
    }

    public function testPostOrganizationWithMissingNameReturns422(): void
    {
        $body = $this->factory->createStream(json_encode([
            'slug' => 'no-name',
            'plan' => 'free',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/organizations')
                ->withBody($body),
        );

        self::assertSame(422, $response->getStatusCode());
    }

    public function testPostOrganizationWithInvalidSlugReturns422(): void
    {
        $body = $this->factory->createStream(json_encode([
            'name' => 'Bad Slug',
            'slug' => 'Bad Slug!!',
            'plan' => 'free',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/organizations')
                ->withBody($body),
        );

        self::assertSame(422, $response->getStatusCode());
    }

    public function testPostOrganizationWithInvalidPlanReturns422(): void
    {
        $body = $this->factory->createStream(json_encode([
            'name' => 'Test',
            'slug' => 'test',
            'plan' => 'platinum',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/organizations')
                ->withBody($body),
        );

        self::assertSame(422, $response->getStatusCode());
    }

    public function testPostDuplicateSlugReturns409(): void
    {
        $body = $this->factory->createStream(json_encode([
            'name' => 'Acme',
            'slug' => 'acme',
        ], JSON_THROW_ON_ERROR));

        $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/organizations')
                ->withBody($body),
        );

        $body2 = $this->factory->createStream(json_encode([
            'name' => 'Acme 2',
            'slug' => 'acme',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/organizations')
                ->withBody($body2),
        );

        self::assertSame(409, $response->getStatusCode());
    }

    // ── GET /api/v1/organizations ──────────────────────────────────────────────

    public function testGetOrganizationsReturnsEmptyList(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/organizations'),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([], $payload['data']);
        self::assertSame(0, $payload['meta']['total']);
    }

    public function testGetOrganizationsReturnsList(): void
    {
        $this->createOrg('Alpha', 'alpha');
        $this->createOrg('Beta', 'beta');

        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/organizations'),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(2, $payload['data']);
        self::assertSame(2, $payload['meta']['total']);
        self::assertSame('alpha', $payload['data'][0]['slug']);
        self::assertSame('beta', $payload['data'][1]['slug']);
    }

    // ── GET /api/v1/organizations/{id} ────────────────────────────────────────

    public function testGetOrganizationByIdReturns200(): void
    {
        $id = $this->createOrg('Acme', 'acme');

        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/organizations/{$id}"),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame($id, $payload['id']);
        self::assertSame('Acme', $payload['name']);
        self::assertSame('acme', $payload['slug']);
    }

    public function testGetOrganizationByNonExistentIdReturns404(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/organizations/999'),
        );

        self::assertSame(404, $response->getStatusCode());
    }

    // ── PATCH /api/v1/organizations/{id} ──────────────────────────────────────

    public function testPatchOrganizationUpdatesFields(): void
    {
        $id = $this->createOrg('Old Name', 'old-name');

        $body = $this->factory->createStream(json_encode([
            'name' => 'New Name',
            'plan' => 'enterprise',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('PATCH', "https://example.test/api/v1/organizations/{$id}")
                ->withBody($body),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('New Name', $payload['name']);
        self::assertSame('enterprise', $payload['plan']);
        self::assertSame('old-name', $payload['slug']); // slug unchanged
    }

    public function testPatchNonExistentOrganizationReturns404(): void
    {
        $body = $this->factory->createStream(json_encode(['name' => 'X'], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('PATCH', 'https://example.test/api/v1/organizations/999')
                ->withBody($body),
        );

        self::assertSame(404, $response->getStatusCode());
    }

    // ── DELETE /api/v1/organizations/{id} ─────────────────────────────────────

    public function testDeleteOrganizationReturns204(): void
    {
        $id = $this->createOrg('To Delete', 'to-delete');

        $response = $this->application->handle(
            $this->factory->createServerRequest('DELETE', "https://example.test/api/v1/organizations/{$id}"),
        );

        self::assertSame(204, $response->getStatusCode());

        // Confirm it's gone
        $getResponse = $this->application->handle(
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/organizations/{$id}"),
        );
        self::assertSame(404, $getResponse->getStatusCode());
    }

    public function testDeleteNonExistentOrganizationReturns404(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('DELETE', 'https://example.test/api/v1/organizations/999'),
        );

        self::assertSame(404, $response->getStatusCode());
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createOrg(string $name, string $slug, string $plan = 'free'): int
    {
        $body = $this->factory->createStream(json_encode([
            'name' => $name,
            'slug' => $slug,
            'plan' => $plan,
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/organizations')
                ->withBody($body),
        );

        return $this->decodeJson($response)['id'];
    }

    /** @return array<string, mixed> */
    private function decodeJson(ResponseInterface $response): array
    {
        $data = json_decode((string) $response->getBody(), true);
        self::assertIsArray($data);

        return $data;
    }
}
