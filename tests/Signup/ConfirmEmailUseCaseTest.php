<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Signup;

use Nene2\Http\SecureTokenHelper;
use NeNeRecords\Signup\ConfirmEmailUseCase;
use NeNeRecords\Tests\User\InMemoryUserRepository;
use NeNeRecords\User\CreateUserInput;
use NeNeRecords\User\CreateUserUseCase;
use NeNeRecords\User\EmailVerificationTokenException;
use PHPUnit\Framework\TestCase;

final class ConfirmEmailUseCaseTest extends TestCase
{
    private function repoWithPendingUser(int $expiresInSeconds): InMemoryUserRepository
    {
        $users = new InMemoryUserRepository([]);
        (new CreateUserUseCase($users))->execute(new CreateUserInput('a@b.test', 'secret-password', 'admin', 1));
        $user = $users->findByEmail('a@b.test');
        self::assertNotNull($user);

        [$raw, $hash] = $this->token;
        $users->storeEmailVerification($user->id, 'a@b.test', $hash, time() + $expiresInSeconds);

        return $users;
    }

    /** @var array{0: string, 1: string} */
    private array $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = SecureTokenHelper::generateWithHash();
    }

    public function testConfirmsValidToken(): void
    {
        $users = $this->repoWithPendingUser(3600);

        (new ConfirmEmailUseCase($users))->execute($this->token[0]);

        $user = $users->findByEmail('a@b.test');
        self::assertNotNull($user);
        self::assertNotNull($user->emailVerifiedAt);
        self::assertNull($user->emailVerificationTokenHash); // token cleared
    }

    public function testRejectsInvalidToken(): void
    {
        $users = $this->repoWithPendingUser(3600);

        $this->expectException(EmailVerificationTokenException::class);
        (new ConfirmEmailUseCase($users))->execute('not-a-real-token');
    }

    public function testRejectsExpiredToken(): void
    {
        $users = $this->repoWithPendingUser(-10); // already expired

        $this->expectException(EmailVerificationTokenException::class);
        (new ConfirmEmailUseCase($users))->execute($this->token[0]);
    }
}
