<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Auth;

use NeNeRecords\Auth\InvalidCredentialsException;
use NeNeRecords\Auth\LoginInput;
use NeNeRecords\Auth\LoginUseCase;
use NeNeRecords\Auth\User;
use NeNeRecords\Tests\User\InMemoryUserRepository;
use Nene2\Auth\TokenIssuerInterface;
use PHPUnit\Framework\TestCase;

final class StubTokenIssuer implements TokenIssuerInterface
{
    public function issue(array $claims): string
    {
        return 'stub-token';
    }
}

final class LoginUseCaseTest extends TestCase
{
    private StubTokenIssuer $tokenIssuer;

    protected function setUp(): void
    {
        $this->tokenIssuer = new StubTokenIssuer();
    }

    public function testReturnsOutputOnValidCredentials(): void
    {
        $hash = password_hash('secret', PASSWORD_BCRYPT);
        $users = new InMemoryUserRepository([
            new User(id: 1, email: 'admin@example.com', passwordHash: $hash, role: 'admin'),
        ]);

        $useCase = new LoginUseCase($users, $this->tokenIssuer);
        $output = $useCase->execute(new LoginInput(email: 'admin@example.com', password: 'secret'));

        self::assertSame('stub-token', $output->token);
        self::assertSame('admin@example.com', $output->email);
        self::assertSame('admin', $output->role);
        self::assertGreaterThan(time(), $output->expiresAt);
    }

    public function testThrowsInvalidCredentialsExceptionWhenUserNotFound(): void
    {
        $users = new InMemoryUserRepository([]);
        $useCase = new LoginUseCase($users, $this->tokenIssuer);

        $this->expectException(InvalidCredentialsException::class);

        $useCase->execute(new LoginInput(email: 'nobody@example.com', password: 'secret'));
    }

    public function testThrowsInvalidCredentialsExceptionWhenPasswordWrong(): void
    {
        $hash = password_hash('correct', PASSWORD_BCRYPT);
        $users = new InMemoryUserRepository([
            new User(id: 1, email: 'admin@example.com', passwordHash: $hash, role: 'admin'),
        ]);

        $useCase = new LoginUseCase($users, $this->tokenIssuer);

        $this->expectException(InvalidCredentialsException::class);

        $useCase->execute(new LoginInput(email: 'admin@example.com', password: 'wrong'));
    }

    public function testThrowsInvalidCredentialsExceptionWhenRoleIsInvalid(): void
    {
        $hash = password_hash('secret', PASSWORD_BCRYPT);
        $users = new InMemoryUserRepository([
            new User(id: 1, email: 'ghost@example.com', passwordHash: $hash, role: 'unknown_role'),
        ]);

        $useCase = new LoginUseCase($users, $this->tokenIssuer);

        $this->expectException(InvalidCredentialsException::class);

        $useCase->execute(new LoginInput(email: 'ghost@example.com', password: 'secret'));
    }
}
