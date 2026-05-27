<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\UserInvite;

use Nene2\Http\SecureTokenHelper;
use NeNeRecords\Auth\User;
use NeNeRecords\Tests\User\InMemoryUserRepository;
use NeNeRecords\User\InvalidUserRoleException;
use NeNeRecords\User\UserEmailConflictException;
use NeNeRecords\UserInvite\AcceptInviteInput;
use NeNeRecords\UserInvite\AcceptInviteUseCase;
use NeNeRecords\UserInvite\ConfirmPasswordResetInput;
use NeNeRecords\UserInvite\ConfirmPasswordResetUseCase;
use NeNeRecords\UserInvite\InvalidInviteTokenException;
use NeNeRecords\UserInvite\InvalidPasswordResetTokenException;
use NeNeRecords\UserInvite\InviteUserInput;
use NeNeRecords\UserInvite\InviteUserUseCase;
use NeNeRecords\UserInvite\RequestPasswordResetInput;
use NeNeRecords\UserInvite\RequestPasswordResetUseCase;
use PHPUnit\Framework\TestCase;

final class UserInviteUseCaseTest extends TestCase
{
    // -----------------------------------------------------------------------
    // InviteUserUseCase
    // -----------------------------------------------------------------------

    public function testInviteUserCreatesUserStoresTokenAndSendsEmail(): void
    {
        $users = new InMemoryUserRepository([]);
        $mailer = new NullMailer();
        $useCase = new InviteUserUseCase($users, $mailer);

        $email = 'new-editor@example.com';
        $output = $useCase->execute(new InviteUserInput(
            email: $email,
            role: 'editor',
            appBaseUrl: 'https://nene-records.example.com',
        ));

        self::assertSame(1, $output->id);
        self::assertSame($email, $output->email);
        self::assertSame('editor', $output->role);
        self::assertSame('invited', $output->status);

        // User should exist in the repository
        $createdUser = $users->findByEmail($email);
        self::assertNotNull($createdUser);
        self::assertNotNull($createdUser->inviteTokenHash);
        self::assertNotNull($createdUser->inviteExpiresAt);

        // Exactly one email should have been sent to the invited address
        self::assertSame(1, count($mailer->sent));
        self::assertSame($email, $mailer->sent[0]->to);
    }

    public function testInviteUserThrowsInvalidUserRoleExceptionForInvalidRole(): void
    {
        $users = new InMemoryUserRepository([]);
        $mailer = new NullMailer();
        $useCase = new InviteUserUseCase($users, $mailer);

        $this->expectException(InvalidUserRoleException::class);

        $useCase->execute(new InviteUserInput(
            email: 'user@example.com',
            role: 'invalid_role',
            appBaseUrl: 'https://nene-records.example.com',
        ));
    }

    public function testInviteUserThrowsUserEmailConflictExceptionForDuplicateEmail(): void
    {
        $hash = password_hash('existing', PASSWORD_BCRYPT);
        $users = new InMemoryUserRepository([
            new User(id: 1, email: 'taken@example.com', passwordHash: $hash, role: 'admin'),
        ]);
        $mailer = new NullMailer();
        $useCase = new InviteUserUseCase($users, $mailer);

        $this->expectException(UserEmailConflictException::class);

        $useCase->execute(new InviteUserInput(
            email: 'taken@example.com',
            role: 'editor',
            appBaseUrl: 'https://nene-records.example.com',
        ));
    }

    // -----------------------------------------------------------------------
    // AcceptInviteUseCase
    // -----------------------------------------------------------------------

    public function testAcceptInviteUpdatesPasswordAndClearsToken(): void
    {
        $users = new InMemoryUserRepository([]);
        $tempHash = password_hash('temp', PASSWORD_BCRYPT);
        $user = $users->create('invited@example.com', $tempHash, 'editor');

        [$rawToken, $tokenHash] = SecureTokenHelper::generateWithHash();
        $users->storeInviteToken($user->id, $tokenHash, time() + 3600);

        $useCase = new AcceptInviteUseCase($users);
        $useCase->execute(new AcceptInviteInput(
            token: $rawToken,
            password: 'my-new-secure-password',
        ));

        $updated = $users->findByEmail('invited@example.com');
        self::assertNotNull($updated);
        self::assertTrue(password_verify('my-new-secure-password', $updated->passwordHash));
        self::assertNull($updated->inviteTokenHash);
        self::assertNull($updated->inviteExpiresAt);
    }

    public function testAcceptInviteThrowsInvalidInviteTokenExceptionForInvalidToken(): void
    {
        $users = new InMemoryUserRepository([]);
        $useCase = new AcceptInviteUseCase($users);

        $this->expectException(InvalidInviteTokenException::class);

        $useCase->execute(new AcceptInviteInput(
            token: 'totally-bogus-token',
            password: 'some-password',
        ));
    }

    public function testAcceptInviteThrowsInvalidInviteTokenExceptionForExpiredToken(): void
    {
        $users = new InMemoryUserRepository([]);
        $tempHash = password_hash('temp', PASSWORD_BCRYPT);
        $user = $users->create('expired@example.com', $tempHash, 'editor');

        [$rawToken, $tokenHash] = SecureTokenHelper::generateWithHash();
        // Store with an already-past expiry
        $users->storeInviteToken($user->id, $tokenHash, time() - 1);

        $useCase = new AcceptInviteUseCase($users);

        $this->expectException(InvalidInviteTokenException::class);

        $useCase->execute(new AcceptInviteInput(
            token: $rawToken,
            password: 'some-password',
        ));
    }

    // -----------------------------------------------------------------------
    // ConfirmPasswordResetUseCase
    // -----------------------------------------------------------------------

    public function testConfirmPasswordResetUpdatesPasswordAndClearsToken(): void
    {
        $users = new InMemoryUserRepository([]);
        $existingHash = password_hash('old-password', PASSWORD_BCRYPT);
        $user = $users->create('reset@example.com', $existingHash, 'admin');

        [$rawToken, $tokenHash] = SecureTokenHelper::generateWithHash();
        $users->storePasswordResetToken($user->id, $tokenHash, time() + 3600);

        $useCase = new ConfirmPasswordResetUseCase($users);
        $useCase->execute(new ConfirmPasswordResetInput(
            token: $rawToken,
            newPassword: 'brand-new-password',
        ));

        $updated = $users->findByEmail('reset@example.com');
        self::assertNotNull($updated);
        self::assertTrue(password_verify('brand-new-password', $updated->passwordHash));
        self::assertNull($updated->passwordResetTokenHash);
        self::assertNull($updated->passwordResetExpiresAt);
    }

    public function testConfirmPasswordResetThrowsInvalidPasswordResetTokenExceptionForInvalidToken(): void
    {
        $users = new InMemoryUserRepository([]);
        $useCase = new ConfirmPasswordResetUseCase($users);

        $this->expectException(InvalidPasswordResetTokenException::class);

        $useCase->execute(new ConfirmPasswordResetInput(
            token: 'not-a-real-token',
            newPassword: 'some-password',
        ));
    }

    public function testConfirmPasswordResetThrowsInvalidPasswordResetTokenExceptionForExpiredToken(): void
    {
        $users = new InMemoryUserRepository([]);
        $existingHash = password_hash('old-password', PASSWORD_BCRYPT);
        $user = $users->create('expired-reset@example.com', $existingHash, 'editor');

        [$rawToken, $tokenHash] = SecureTokenHelper::generateWithHash();
        // Store with an already-past expiry
        $users->storePasswordResetToken($user->id, $tokenHash, time() - 1);

        $useCase = new ConfirmPasswordResetUseCase($users);

        $this->expectException(InvalidPasswordResetTokenException::class);

        $useCase->execute(new ConfirmPasswordResetInput(
            token: $rawToken,
            newPassword: 'some-password',
        ));
    }

    // -----------------------------------------------------------------------
    // RequestPasswordResetUseCase
    // -----------------------------------------------------------------------

    public function testRequestPasswordResetSendsEmailForKnownUser(): void
    {
        $hash = password_hash('password', PASSWORD_BCRYPT);
        $users = new InMemoryUserRepository([
            new User(id: 1, email: 'known@example.com', passwordHash: $hash, role: 'editor'),
        ]);
        $mailer = new NullMailer();
        $useCase = new RequestPasswordResetUseCase($users, $mailer);

        $useCase->execute(new RequestPasswordResetInput(
            email: 'known@example.com',
            appBaseUrl: 'https://nene-records.example.com',
        ));

        self::assertSame(1, count($mailer->sent));
        self::assertSame('known@example.com', $mailer->sent[0]->to);
    }

    public function testRequestPasswordResetDoesNotThrowForUnknownEmail(): void
    {
        $users = new InMemoryUserRepository([]);
        $mailer = new NullMailer();
        $useCase = new RequestPasswordResetUseCase($users, $mailer);

        // Must not throw — silently succeeds to prevent email enumeration
        $useCase->execute(new RequestPasswordResetInput(
            email: 'nobody@example.com',
            appBaseUrl: 'https://nene-records.example.com',
        ));

        // No email should have been sent
        self::assertSame(0, count($mailer->sent));
    }
}
