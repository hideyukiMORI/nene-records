<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\User;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use NeNeRecords\Auth\User;
use NeNeRecords\User\CannotDeleteSelfExceptionHandler;
use NeNeRecords\User\ChangePasswordHandler;
use NeNeRecords\User\ChangePasswordUseCase;
use NeNeRecords\User\CreateUserHandler;
use NeNeRecords\User\CreateUserUseCase;
use NeNeRecords\User\DeleteUserHandler;
use NeNeRecords\User\DeleteUserUseCase;
use NeNeRecords\User\GetUserByIdHandler;
use NeNeRecords\User\GetUserByIdUseCase;
use NeNeRecords\User\InvalidCurrentPasswordExceptionHandler;
use NeNeRecords\User\InvalidUserRoleExceptionHandler;
use NeNeRecords\User\ListUsersHandler;
use NeNeRecords\User\ListUsersUseCase;
use NeNeRecords\User\ResetUserPasswordHandler;
use NeNeRecords\User\ResetUserPasswordUseCase;
use NeNeRecords\User\UpdateUserRoleHandler;
use NeNeRecords\User\UpdateUserRoleUseCase;
use NeNeRecords\User\UserEmailConflictExceptionHandler;
use NeNeRecords\User\UserNotFoundExceptionHandler;
use NeNeRecords\User\UserRouteRegistrar;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UserHttpTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryUserRepository $repository;
    private RequestHandlerInterface $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();

        // Seed with an admin user
        $this->repository = new InMemoryUserRepository([
            new User(
                id: 1,
                email: 'admin@example.test',
                passwordHash: password_hash('secret123', PASSWORD_BCRYPT),
                role: 'admin',
                status: 'active',
                createdAt: 1700000000,
                updatedAt: 1700000000,
            ),
        ]);

        $jsonResponse = new JsonResponseFactory($this->factory, $this->factory);
        $problemDetails = new ProblemDetailsResponseFactory($this->factory, $this->factory);

        $registrar = new UserRouteRegistrar(
            new ListUsersHandler(new ListUsersUseCase($this->repository), $jsonResponse),
            new GetUserByIdHandler(new GetUserByIdUseCase($this->repository), $jsonResponse),
            new CreateUserHandler(new CreateUserUseCase($this->repository), $jsonResponse),
            new UpdateUserRoleHandler(new UpdateUserRoleUseCase($this->repository), $jsonResponse),
            new ResetUserPasswordHandler(new ResetUserPasswordUseCase($this->repository), $this->factory),
            new DeleteUserHandler(new DeleteUserUseCase($this->repository), $this->factory),
            new ChangePasswordHandler(new ChangePasswordUseCase($this->repository), $this->factory),
        );

        $this->application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [
                new UserNotFoundExceptionHandler($problemDetails),
                new UserEmailConflictExceptionHandler($problemDetails),
                new CannotDeleteSelfExceptionHandler($problemDetails),
                new InvalidUserRoleExceptionHandler($problemDetails),
                new InvalidCurrentPasswordExceptionHandler($problemDetails),
            ],
            routeRegistrars: [$registrar],
        ))->create();
    }

    // ── List ────────────────────────────────────────────────────────────────────

    public function testGetUsersReturnsListWithSeededUser(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/users'),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertIsArray($payload['items']);
        self::assertCount(1, $payload['items']);
        self::assertSame('admin@example.test', $payload['items'][0]['email']);
        self::assertSame('admin', $payload['items'][0]['role']);
        self::assertSame('active', $payload['items'][0]['status']);
    }

    // ── Create ──────────────────────────────────────────────────────────────────

    public function testPostUserCreatesUserAndReturns201(): void
    {
        $body = $this->factory->createStream(json_encode([
            'email' => 'editor@example.test',
            'password' => 'password123',
            'role' => 'editor',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/users')->withBody($body),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(201, $response->getStatusCode());
        self::assertStringStartsWith('/api/v1/users/', $response->getHeaderLine('Location'));
        self::assertSame('editor@example.test', $payload['email']);
        self::assertSame('editor', $payload['role']);
        self::assertSame('active', $payload['status']);
    }

    public function testPostDuplicateEmailReturns409(): void
    {
        $body = $this->factory->createStream(json_encode([
            'email' => 'admin@example.test',
            'password' => 'password123',
            'role' => 'admin',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/users')->withBody($body),
        );

        self::assertSame(409, $response->getStatusCode());
    }

    public function testPostInvalidRoleReturns422(): void
    {
        $body = $this->factory->createStream(json_encode([
            'email' => 'new@example.test',
            'password' => 'password123',
            'role' => 'superadmin',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/users')->withBody($body),
        );

        self::assertSame(422, $response->getStatusCode());
    }

    // ── Get by ID ──────────────────────────────────────────────────────────────

    public function testGetUserByIdReturns200(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/users/1'),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(1, $payload['id']);
        self::assertSame('admin@example.test', $payload['email']);
    }

    public function testGetNonExistentUserReturns404(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/users/999'),
        );

        self::assertSame(404, $response->getStatusCode());
    }

    // ── Update role ─────────────────────────────────────────────────────────────

    public function testPatchUserRoleUpdatesRole(): void
    {
        $body = $this->factory->createStream(json_encode(['role' => 'editor'], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('PATCH', 'https://example.test/api/v1/users/1')->withBody($body),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('editor', $payload['role']);
    }

    // ── Admin password reset ────────────────────────────────────────────────────

    public function testPatchUserPasswordReturns204(): void
    {
        $body = $this->factory->createStream(json_encode(['new_password' => 'newpassword123'], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('PATCH', 'https://example.test/api/v1/users/1/password')->withBody($body),
        );

        self::assertSame(204, $response->getStatusCode());
    }

    // ── Delete ──────────────────────────────────────────────────────────────────

    public function testDeleteUserReturns204(): void
    {
        // First create a second user so admin is not the last one
        $createBody = $this->factory->createStream(json_encode([
            'email' => 'admin2@example.test',
            'password' => 'password123',
            'role' => 'admin',
        ], JSON_THROW_ON_ERROR));

        $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/users')->withBody($createBody),
        );

        $response = $this->application->handle(
            $this->factory->createServerRequest('DELETE', 'https://example.test/api/v1/users/1')
                ->withAttribute('nene2.auth.claims', ['sub' => 'other@example.test']),
        );

        self::assertSame(204, $response->getStatusCode());
    }

    public function testDeleteSelfReturns403(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('DELETE', 'https://example.test/api/v1/users/1')
                ->withAttribute('nene2.auth.claims', ['sub' => 'admin@example.test']),
        );

        self::assertSame(403, $response->getStatusCode());
    }

    public function testDeleteLastAdminReturns403(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('DELETE', 'https://example.test/api/v1/users/1')
                ->withAttribute('nene2.auth.claims', ['sub' => 'other@example.test']),
        );

        self::assertSame(403, $response->getStatusCode());
    }

    // ── Change own password ─────────────────────────────────────────────────────

    public function testPutMePasswordReturns204(): void
    {
        $body = $this->factory->createStream(json_encode([
            'current_password' => 'secret123',
            'new_password' => 'newsecret456',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('PUT', 'https://example.test/api/v1/users/me/password')
                ->withBody($body)
                ->withAttribute('nene2.auth.claims', ['sub' => 'admin@example.test']),
        );

        self::assertSame(204, $response->getStatusCode());
    }

    public function testPutMePasswordWithWrongCurrentPasswordReturns422(): void
    {
        $body = $this->factory->createStream(json_encode([
            'current_password' => 'wrongpassword',
            'new_password' => 'newsecret456',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('PUT', 'https://example.test/api/v1/users/me/password')
                ->withBody($body)
                ->withAttribute('nene2.auth.claims', ['sub' => 'admin@example.test']),
        );

        self::assertSame(422, $response->getStatusCode());
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
}
