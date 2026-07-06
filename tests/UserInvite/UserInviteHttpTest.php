<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\UserInvite;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use Nene2\Http\UtcClock;
use NeNeRecords\Auth\User;
use NeNeRecords\Tests\User\InMemoryUserRepository;
use NeNeRecords\User\InvalidUserRoleExceptionHandler;
use NeNeRecords\User\UserEmailConflictExceptionHandler;
use NeNeRecords\UserInvite\AcceptInviteHandler;
use NeNeRecords\UserInvite\AcceptInviteUseCase;
use NeNeRecords\UserInvite\ConfirmPasswordResetHandler;
use NeNeRecords\UserInvite\ConfirmPasswordResetUseCase;
use NeNeRecords\UserInvite\InvalidInviteTokenExceptionHandler;
use NeNeRecords\UserInvite\InvalidPasswordResetTokenExceptionHandler;
use NeNeRecords\UserInvite\InviteUserHandler;
use NeNeRecords\UserInvite\InviteUserUseCase;
use NeNeRecords\UserInvite\RequestPasswordResetHandler;
use NeNeRecords\UserInvite\RequestPasswordResetUseCase;
use NeNeRecords\UserInvite\UserInviteRouteRegistrar;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UserInviteHttpTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryUserRepository $repository;
    private NullMailer $mailer;
    private RequestHandlerInterface $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();
        $this->repository = new InMemoryUserRepository([
            new User(
                id: 1,
                email: 'admin@example.test',
                passwordHash: password_hash('secret123', PASSWORD_BCRYPT),
                role: 'admin',
                status: 'active',
                createdAt: time(),
                updatedAt: time(),
            ),
        ]);
        $this->mailer = new NullMailer();

        $jsonResponse = new JsonResponseFactory($this->factory, $this->factory);
        $problemDetails = new ProblemDetailsResponseFactory($this->factory, $this->factory);

        $registrar = new UserInviteRouteRegistrar(
            new InviteUserHandler(new InviteUserUseCase($this->repository, $this->mailer, new UtcClock()), $jsonResponse),
            new AcceptInviteHandler(new AcceptInviteUseCase($this->repository, new UtcClock()), $this->factory),
            new RequestPasswordResetHandler(new RequestPasswordResetUseCase($this->repository, $this->mailer, new UtcClock()), $this->factory),
            new ConfirmPasswordResetHandler(new ConfirmPasswordResetUseCase($this->repository, new UtcClock()), $this->factory),
        );

        $this->application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [
                new UserEmailConflictExceptionHandler($problemDetails),
                new InvalidUserRoleExceptionHandler($problemDetails),
                new InvalidInviteTokenExceptionHandler($problemDetails),
                new InvalidPasswordResetTokenExceptionHandler($problemDetails),
            ],
            routeRegistrars: [$registrar],
        ))->create();
    }

    // ── Invite ──────────────────────────────────────────────────────────────────

    public function testPostInviteCreatesInvitedUserAndSendsEmail(): void
    {
        $body = $this->factory->createStream(json_encode([
            'email' => 'neweditor@example.test',
            'role' => 'editor',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/users/invite')->withBody($body),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame('neweditor@example.test', $payload['email']);
        self::assertSame('invited', $payload['status']);
        self::assertCount(1, $this->mailer->sent);
        self::assertSame('neweditor@example.test', $this->mailer->sent[0]->to);
    }

    public function testPostInviteDuplicateEmailReturns409(): void
    {
        $body = $this->factory->createStream(json_encode([
            'email' => 'admin@example.test',
            'role' => 'editor',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/users/invite')->withBody($body),
        );

        self::assertSame(409, $response->getStatusCode());
    }

    // ── Accept invite ───────────────────────────────────────────────────────────

    public function testPostAcceptInviteActivatesUser(): void
    {
        // First create an invite
        $inviteBody = $this->factory->createStream(json_encode([
            'email' => 'invited@example.test',
            'role' => 'editor',
        ], JSON_THROW_ON_ERROR));

        $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/users/invite')->withBody($inviteBody),
        );

        // Extract raw token from the email body
        $emailBody = $this->mailer->sent[0]->textBody;
        $rawToken = $this->extractToken($emailBody);

        // Accept the invite
        $acceptBody = $this->factory->createStream(json_encode([
            'token' => $rawToken,
            'password' => 'newpassword123',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/auth/accept-invite')->withBody($acceptBody),
        );

        self::assertSame(204, $response->getStatusCode());

        // User should now be active
        $user = $this->repository->findByEmail('invited@example.test');
        self::assertNotNull($user);
        self::assertSame('active', $user->status);
        self::assertNull($user->inviteTokenHash);
    }

    public function testPostAcceptInviteWithInvalidTokenReturns422(): void
    {
        $body = $this->factory->createStream(json_encode([
            'token' => str_repeat('a', 64),
            'password' => 'newpassword123',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/auth/accept-invite')->withBody($body),
        );

        self::assertSame(422, $response->getStatusCode());
    }

    // ── Password reset ──────────────────────────────────────────────────────────

    public function testPostPasswordResetAlwaysReturns204(): void
    {
        $body = $this->factory->createStream(json_encode([
            'email' => 'admin@example.test',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/auth/password-reset')->withBody($body),
        );

        self::assertSame(204, $response->getStatusCode());
        self::assertCount(1, $this->mailer->sent);
    }

    public function testPostPasswordResetForUnknownEmailAlsoReturns204(): void
    {
        $body = $this->factory->createStream(json_encode([
            'email' => 'unknown@example.test',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/auth/password-reset')->withBody($body),
        );

        // Must NOT reveal whether email exists
        self::assertSame(204, $response->getStatusCode());
        self::assertCount(0, $this->mailer->sent);
    }

    public function testPostConfirmPasswordResetChangesPassword(): void
    {
        // Request a reset
        $requestBody = $this->factory->createStream(json_encode([
            'email' => 'admin@example.test',
        ], JSON_THROW_ON_ERROR));

        $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/auth/password-reset')->withBody($requestBody),
        );

        // Extract raw token from email
        $emailBody = $this->mailer->sent[0]->textBody;
        $rawToken = $this->extractToken($emailBody);

        // Confirm reset
        $confirmBody = $this->factory->createStream(json_encode([
            'token' => $rawToken,
            'new_password' => 'newpassword456',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/auth/password-reset/confirm')->withBody($confirmBody),
        );

        self::assertSame(204, $response->getStatusCode());

        // Verify new password works
        $user = $this->repository->findByEmail('admin@example.test');
        self::assertNotNull($user);
        self::assertTrue(password_verify('newpassword456', $user->passwordHash));
        self::assertNull($user->passwordResetTokenHash);
    }

    public function testPostConfirmPasswordResetWithInvalidTokenReturns422(): void
    {
        $body = $this->factory->createStream(json_encode([
            'token' => str_repeat('b', 64),
            'new_password' => 'newpassword456',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/auth/password-reset/confirm')->withBody($body),
        );

        self::assertSame(422, $response->getStatusCode());
    }

    private function extractToken(string $body): string
    {
        if (preg_match('/token=([a-f0-9]+)/', $body, $matches) !== 1) {
            self::fail('Token not found in email body.');
        }

        return (string) $matches[1];
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
