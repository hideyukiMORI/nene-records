<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\User;

use NeNeRecords\Auth\User;
use NeNeRecords\User\CannotDeleteLastAdminException;
use NeNeRecords\User\CannotDeleteSelfException;
use NeNeRecords\User\ChangePasswordInput;
use NeNeRecords\User\ChangePasswordUseCase;
use NeNeRecords\User\CreateUserInput;
use NeNeRecords\User\CreateUserUseCase;
use NeNeRecords\User\DeleteUserInput;
use NeNeRecords\User\DeleteUserUseCase;
use NeNeRecords\User\GetUserByIdInput;
use NeNeRecords\User\GetUserByIdUseCase;
use NeNeRecords\User\InvalidCurrentPasswordException;
use NeNeRecords\User\InvalidUserRoleException;
use NeNeRecords\User\ResetUserPasswordInput;
use NeNeRecords\User\ResetUserPasswordUseCase;
use NeNeRecords\User\UpdateUserRoleInput;
use NeNeRecords\User\UpdateUserRoleUseCase;
use NeNeRecords\User\UserEmailConflictException;
use NeNeRecords\User\UserNotFoundException;
use PHPUnit\Framework\TestCase;

final class UserUseCaseTest extends TestCase
{
    // -----------------------------------------------------------------------
    // CreateUserUseCase
    // -----------------------------------------------------------------------

    public function testCreateUserReturnsOutput(): void
    {
        $users = new InMemoryUserRepository([]);
        $useCase = new CreateUserUseCase($users);

        $output = $useCase->execute(new CreateUserInput(
            email: 'editor@example.com',
            password: 'password123',
            role: 'editor',
        ));

        self::assertSame(1, $output->id);
        self::assertSame('editor@example.com', $output->email);
        self::assertSame('editor', $output->role);
        self::assertSame('active', $output->status);
    }

    public function testCreateUserThrowsInvalidUserRoleExceptionForInvalidRole(): void
    {
        $users = new InMemoryUserRepository([]);
        $useCase = new CreateUserUseCase($users);

        $this->expectException(InvalidUserRoleException::class);

        $useCase->execute(new CreateUserInput(
            email: 'user@example.com',
            password: 'password123',
            role: 'invalid_role',
        ));
    }

    public function testCreateUserThrowsInvalidUserRoleExceptionForSuperadminRole(): void
    {
        $users = new InMemoryUserRepository([]);
        $useCase = new CreateUserUseCase($users);

        $this->expectException(InvalidUserRoleException::class);

        $useCase->execute(new CreateUserInput(
            email: 'super@example.com',
            password: 'password123',
            role: 'superadmin',
        ));
    }

    public function testCreateUserThrowsUserEmailConflictExceptionForDuplicateEmail(): void
    {
        $hash = password_hash('existing', PASSWORD_BCRYPT);
        $users = new InMemoryUserRepository([
            new User(id: 1, email: 'taken@example.com', passwordHash: $hash, role: 'admin'),
        ]);

        $useCase = new CreateUserUseCase($users);

        $this->expectException(UserEmailConflictException::class);

        $useCase->execute(new CreateUserInput(
            email: 'taken@example.com',
            password: 'password123',
            role: 'editor',
        ));
    }

    // -----------------------------------------------------------------------
    // ChangePasswordUseCase
    // -----------------------------------------------------------------------

    public function testChangePasswordSuccessfully(): void
    {
        $hash = password_hash('old_password', PASSWORD_BCRYPT);
        $users = new InMemoryUserRepository([
            new User(id: 1, email: 'user@example.com', passwordHash: $hash, role: 'editor'),
        ]);

        $useCase = new ChangePasswordUseCase($users);
        $useCase->execute(new ChangePasswordInput(
            currentUserEmail: 'user@example.com',
            currentPassword: 'old_password',
            newPassword: 'new_password',
        ));

        $updated = $users->findByEmail('user@example.com');
        self::assertNotNull($updated);
        self::assertTrue(password_verify('new_password', $updated->passwordHash));
    }

    public function testChangePasswordThrowsUserNotFoundExceptionWhenUserNotFound(): void
    {
        $users = new InMemoryUserRepository([]);
        $useCase = new ChangePasswordUseCase($users);

        $this->expectException(UserNotFoundException::class);

        $useCase->execute(new ChangePasswordInput(
            currentUserEmail: 'nobody@example.com',
            currentPassword: 'old_password',
            newPassword: 'new_password',
        ));
    }

    public function testChangePasswordThrowsInvalidCurrentPasswordExceptionWhenPasswordWrong(): void
    {
        $hash = password_hash('correct_password', PASSWORD_BCRYPT);
        $users = new InMemoryUserRepository([
            new User(id: 1, email: 'user@example.com', passwordHash: $hash, role: 'editor'),
        ]);

        $useCase = new ChangePasswordUseCase($users);

        $this->expectException(InvalidCurrentPasswordException::class);

        $useCase->execute(new ChangePasswordInput(
            currentUserEmail: 'user@example.com',
            currentPassword: 'wrong_password',
            newPassword: 'new_password',
        ));
    }

    // -----------------------------------------------------------------------
    // DeleteUserUseCase
    // -----------------------------------------------------------------------

    public function testDeleteUserSuccessfully(): void
    {
        $hash = password_hash('password', PASSWORD_BCRYPT);
        $users = new InMemoryUserRepository([
            new User(id: 1, email: 'admin@example.com', passwordHash: $hash, role: 'admin'),
            new User(id: 2, email: 'editor@example.com', passwordHash: $hash, role: 'editor'),
        ]);

        $useCase = new DeleteUserUseCase($users);
        $useCase->execute(new DeleteUserInput(id: 2, currentUserEmail: 'admin@example.com'));

        self::assertNull($users->findById(2));
    }

    public function testDeleteUserThrowsUserNotFoundExceptionWhenUserNotFound(): void
    {
        $users = new InMemoryUserRepository([]);
        $useCase = new DeleteUserUseCase($users);

        $this->expectException(UserNotFoundException::class);

        $useCase->execute(new DeleteUserInput(id: 99, currentUserEmail: 'admin@example.com'));
    }

    public function testDeleteUserThrowsCannotDeleteSelfException(): void
    {
        $hash = password_hash('password', PASSWORD_BCRYPT);
        $users = new InMemoryUserRepository([
            new User(id: 1, email: 'admin@example.com', passwordHash: $hash, role: 'admin'),
        ]);

        $useCase = new DeleteUserUseCase($users);

        $this->expectException(CannotDeleteSelfException::class);

        $useCase->execute(new DeleteUserInput(id: 1, currentUserEmail: 'admin@example.com'));
    }

    public function testDeleteUserThrowsCannotDeleteLastAdminException(): void
    {
        $hash = password_hash('password', PASSWORD_BCRYPT);
        $users = new InMemoryUserRepository([
            new User(id: 1, email: 'last-admin@example.com', passwordHash: $hash, role: 'admin'),
        ]);

        $useCase = new DeleteUserUseCase($users);

        $this->expectException(CannotDeleteLastAdminException::class);

        $useCase->execute(new DeleteUserInput(id: 1, currentUserEmail: 'other@example.com'));
    }

    // -----------------------------------------------------------------------
    // UpdateUserRoleUseCase
    // -----------------------------------------------------------------------

    public function testUpdateUserRoleReturnsCorrectOutput(): void
    {
        $hash = password_hash('password', PASSWORD_BCRYPT);
        $users = new InMemoryUserRepository([
            new User(id: 1, email: 'user@example.com', passwordHash: $hash, role: 'editor'),
        ]);

        $useCase = new UpdateUserRoleUseCase($users);
        $output = $useCase->execute(new UpdateUserRoleInput(id: 1, role: 'admin'));

        self::assertSame(1, $output->id);
        self::assertSame('user@example.com', $output->email);
        self::assertSame('admin', $output->role);
        self::assertSame('active', $output->status);
    }

    public function testUpdateUserRoleThrowsInvalidUserRoleExceptionForInvalidRole(): void
    {
        $hash = password_hash('password', PASSWORD_BCRYPT);
        $users = new InMemoryUserRepository([
            new User(id: 1, email: 'user@example.com', passwordHash: $hash, role: 'editor'),
        ]);

        $useCase = new UpdateUserRoleUseCase($users);

        $this->expectException(InvalidUserRoleException::class);

        $useCase->execute(new UpdateUserRoleInput(id: 1, role: 'invalid_role'));
    }

    public function testUpdateUserRoleThrowsUserNotFoundExceptionWhenUserNotFound(): void
    {
        $users = new InMemoryUserRepository([]);
        $useCase = new UpdateUserRoleUseCase($users);

        $this->expectException(UserNotFoundException::class);

        $useCase->execute(new UpdateUserRoleInput(id: 99, role: 'admin'));
    }

    // -----------------------------------------------------------------------
    // ResetUserPasswordUseCase
    // -----------------------------------------------------------------------

    public function testResetUserPasswordUpdatesHash(): void
    {
        $hash = password_hash('old_password', PASSWORD_BCRYPT);
        $users = new InMemoryUserRepository([
            new User(id: 1, email: 'user@example.com', passwordHash: $hash, role: 'editor'),
        ]);

        $useCase = new ResetUserPasswordUseCase($users);
        $useCase->execute(new ResetUserPasswordInput(id: 1, newPassword: 'reset_password'));

        $updated = $users->findById(1);
        self::assertNotNull($updated);
        self::assertTrue(password_verify('reset_password', $updated->passwordHash));
    }

    public function testResetUserPasswordThrowsUserNotFoundExceptionForUnknownId(): void
    {
        $users = new InMemoryUserRepository([]);
        $useCase = new ResetUserPasswordUseCase($users);

        $this->expectException(UserNotFoundException::class);

        $useCase->execute(new ResetUserPasswordInput(id: 99, newPassword: 'newpass'));
    }

    // -----------------------------------------------------------------------
    // GetUserByIdUseCase
    // -----------------------------------------------------------------------

    public function testGetUserByIdReturnsCorrectOutputFields(): void
    {
        $hash = password_hash('password', PASSWORD_BCRYPT);
        $users = new InMemoryUserRepository([
            new User(id: 1, email: 'admin@example.com', passwordHash: $hash, role: 'admin', status: 'active', createdAt: 1000, updatedAt: 2000),
        ]);

        $useCase = new GetUserByIdUseCase($users, new InMemoryUserProfileRepository());
        $output = $useCase->execute(new GetUserByIdInput(id: 1));

        self::assertSame(1, $output->id);
        self::assertSame('admin@example.com', $output->email);
        self::assertSame('admin', $output->role);
        self::assertSame('active', $output->status);
        self::assertSame(1000, $output->createdAt);
        self::assertSame(2000, $output->updatedAt);
    }

    public function testGetUserByIdThrowsUserNotFoundExceptionForUnknownId(): void
    {
        $users = new InMemoryUserRepository([]);
        $useCase = new GetUserByIdUseCase($users, new InMemoryUserProfileRepository());

        $this->expectException(UserNotFoundException::class);

        $useCase->execute(new GetUserByIdInput(id: 99));
    }
}
