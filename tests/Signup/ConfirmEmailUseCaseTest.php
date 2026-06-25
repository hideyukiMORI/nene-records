<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Signup;

use Nene2\Http\SecureTokenHelper;
use NeNeRecords\Organization\Organization;
use NeNeRecords\Signup\ConfirmEmailUseCase;
use NeNeRecords\Tests\Organization\InMemoryOrganizationRepository;
use NeNeRecords\Tests\User\InMemoryUserRepository;
use NeNeRecords\User\CreateUserInput;
use NeNeRecords\User\CreateUserUseCase;
use NeNeRecords\User\EmailVerificationTokenException;
use PHPUnit\Framework\TestCase;

final class ConfirmEmailUseCaseTest extends TestCase
{
    private InMemoryUserRepository $users;
    private InMemoryOrganizationRepository $orgs;
    /** @var array{0: string, 1: string} */
    private array $token;
    private int $orgId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = SecureTokenHelper::generateWithHash();
        $this->orgs = new InMemoryOrganizationRepository();
        $this->orgId = $this->orgs->save(new Organization('My Org', 'myorg', 'free', true));
    }

    private function withPendingUser(int $expiresInSeconds): ConfirmEmailUseCase
    {
        $this->users = new InMemoryUserRepository([]);
        (new CreateUserUseCase($this->users))->execute(
            new CreateUserInput('a@b.test', 'secret-password', 'admin', $this->orgId),
        );
        $user = $this->users->findByEmail('a@b.test');
        self::assertNotNull($user);
        $this->users->storeEmailVerification($user->id, 'a@b.test', $this->token[1], time() + $expiresInSeconds);

        return new ConfirmEmailUseCase($this->users, $this->orgs);
    }

    public function testConfirmsValidTokenAndReturnsSlug(): void
    {
        $slug = $this->withPendingUser(3600)->execute($this->token[0]);

        self::assertSame('myorg', $slug);
        $user = $this->users->findByEmail('a@b.test');
        self::assertNotNull($user);
        self::assertNotNull($user->emailVerifiedAt);
        self::assertNull($user->emailVerificationTokenHash); // token cleared
    }

    public function testRejectsInvalidToken(): void
    {
        $useCase = $this->withPendingUser(3600);

        $this->expectException(EmailVerificationTokenException::class);
        $useCase->execute('not-a-real-token');
    }

    public function testRejectsExpiredToken(): void
    {
        $useCase = $this->withPendingUser(-10); // already expired

        $this->expectException(EmailVerificationTokenException::class);
        $useCase->execute($this->token[0]);
    }
}
