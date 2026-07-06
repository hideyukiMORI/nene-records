<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Auth;

use Nene2\Auth\TokenIssuerInterface;
use NeNeRecords\Auth\InvalidCredentialsException;
use NeNeRecords\Auth\LoginInput;
use NeNeRecords\Auth\LoginUseCase;
use NeNeRecords\Auth\User;
use NeNeRecords\Tests\Support\FixedClock;
use NeNeRecords\Tests\User\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;

final class StubTokenIssuer implements TokenIssuerInterface
{
    /** @var array<string, mixed>|null */
    public ?array $lastClaims = null;

    public function issue(array $claims): string
    {
        $this->lastClaims = $claims;

        return 'stub-token';
    }
}

final class LoginUseCaseTest extends TestCase
{
    /** Fixed instant so token iat/exp are deterministic under test. */
    private const FIXED_INSTANT = '2026-06-01T10:00:00+00:00';

    private StubTokenIssuer $tokenIssuer;

    private FixedClock $clock;

    protected function setUp(): void
    {
        $this->tokenIssuer = new StubTokenIssuer();
        $this->clock = new FixedClock(self::FIXED_INSTANT);
    }

    public function testReturnsOutputOnValidCredentials(): void
    {
        $hash = password_hash('secret', PASSWORD_BCRYPT);
        $users = new InMemoryUserRepository([
            new User(id: 1, email: 'admin@example.com', passwordHash: $hash, role: 'admin'),
        ]);

        $useCase = new LoginUseCase($users, $this->tokenIssuer, $this->clock);
        $output = $useCase->execute(new LoginInput(email: 'admin@example.com', password: 'secret'));

        self::assertSame('stub-token', $output->token);
        self::assertSame('admin@example.com', $output->email);
        self::assertSame('admin', $output->role);
        self::assertSame((new \DateTimeImmutable(self::FIXED_INSTANT))->getTimestamp() + 86400, $output->expiresAt);
    }

    public function testThrowsInvalidCredentialsExceptionWhenUserNotFound(): void
    {
        $users = new InMemoryUserRepository([]);
        $useCase = new LoginUseCase($users, $this->tokenIssuer, $this->clock);

        $this->expectException(InvalidCredentialsException::class);

        $useCase->execute(new LoginInput(email: 'nobody@example.com', password: 'secret'));
    }

    public function testThrowsInvalidCredentialsExceptionWhenPasswordWrong(): void
    {
        $hash = password_hash('correct', PASSWORD_BCRYPT);
        $users = new InMemoryUserRepository([
            new User(id: 1, email: 'admin@example.com', passwordHash: $hash, role: 'admin'),
        ]);

        $useCase = new LoginUseCase($users, $this->tokenIssuer, $this->clock);

        $this->expectException(InvalidCredentialsException::class);

        $useCase->execute(new LoginInput(email: 'admin@example.com', password: 'wrong'));
    }

    public function testThrowsInvalidCredentialsExceptionWhenRoleIsInvalid(): void
    {
        $hash = password_hash('secret', PASSWORD_BCRYPT);
        $users = new InMemoryUserRepository([
            new User(id: 1, email: 'ghost@example.com', passwordHash: $hash, role: 'unknown_role'),
        ]);

        $useCase = new LoginUseCase($users, $this->tokenIssuer, $this->clock);

        $this->expectException(InvalidCredentialsException::class);

        $useCase->execute(new LoginInput(email: 'ghost@example.com', password: 'secret'));
    }

    // ── org_id in JWT output ─────────────────────────────────────────────────────

    /**
     * admin ユーザーのログイン出力には organizationId が org_id として含まれる
     */
    public function testAdminLoginOutputContainsOrgId(): void
    {
        $hash = password_hash('secret', PASSWORD_BCRYPT);
        $users = new InMemoryUserRepository([
            new User(id: 1, email: 'admin@example.com', passwordHash: $hash, role: 'admin', organizationId: 42),
        ]);

        $useCase = new LoginUseCase($users, $this->tokenIssuer, $this->clock);
        $output = $useCase->execute(new LoginInput(email: 'admin@example.com', password: 'secret'));

        self::assertSame(42, $output->orgId);
        self::assertSame('admin', $output->role);
    }

    /**
     * superadmin のログイン出力では org_id は null（組織に属さない）
     */
    public function testSuperadminLoginOutputHasNullOrgId(): void
    {
        $hash = password_hash('secret', PASSWORD_BCRYPT);
        $users = new InMemoryUserRepository([
            // superadmin は organizationId を持たない
            new User(id: 2, email: 'sa@example.com', passwordHash: $hash, role: 'superadmin', organizationId: null),
        ]);

        $useCase = new LoginUseCase($users, $this->tokenIssuer, $this->clock);
        $output = $useCase->execute(new LoginInput(email: 'sa@example.com', password: 'secret'));

        self::assertNull($output->orgId);
        self::assertSame('superadmin', $output->role);
    }

    /**
     * editor ユーザーのログイン出力にも organizationId が含まれる
     */
    public function testEditorLoginOutputContainsOrgId(): void
    {
        $hash = password_hash('secret', PASSWORD_BCRYPT);
        $users = new InMemoryUserRepository([
            new User(id: 3, email: 'editor@example.com', passwordHash: $hash, role: 'editor', organizationId: 7),
        ]);

        $useCase = new LoginUseCase($users, $this->tokenIssuer, $this->clock);
        $output = $useCase->execute(new LoginInput(email: 'editor@example.com', password: 'secret'));

        self::assertSame(7, $output->orgId);
        self::assertSame('editor', $output->role);
    }

    /**
     * admin が組織未割当（organizationId=null）の場合、org_id は null
     */
    public function testAdminWithNullOrganizationIdHasNullOrgId(): void
    {
        $hash = password_hash('secret', PASSWORD_BCRYPT);
        $users = new InMemoryUserRepository([
            new User(id: 4, email: 'unassigned@example.com', passwordHash: $hash, role: 'admin', organizationId: null),
        ]);

        $useCase = new LoginUseCase($users, $this->tokenIssuer, $this->clock);
        $output = $useCase->execute(new LoginInput(email: 'unassigned@example.com', password: 'secret'));

        self::assertNull($output->orgId);
        self::assertSame('admin', $output->role);
    }

    /**
     * FixedClock を注入すると iat / exp / expiresAt が実クロックに依存せず決定論的になる。
     * iat は固定時刻の Unix 秒、exp と expiresAt は固定時刻 + TOKEN_TTL(86400) に厳密一致する。
     */
    public function testTokenClaimsAreDeterministicWithFixedClock(): void
    {
        $fixed = (new \DateTimeImmutable(self::FIXED_INSTANT))->getTimestamp();

        $hash = password_hash('secret', PASSWORD_BCRYPT);
        $users = new InMemoryUserRepository([
            new User(id: 1, email: 'admin@example.com', passwordHash: $hash, role: 'admin', organizationId: 42),
        ]);

        $useCase = new LoginUseCase($users, $this->tokenIssuer, $this->clock);
        $output = $useCase->execute(new LoginInput(email: 'admin@example.com', password: 'secret'));

        self::assertSame($fixed + 86400, $output->expiresAt);
        self::assertNotNull($this->tokenIssuer->lastClaims);
        self::assertSame($fixed, $this->tokenIssuer->lastClaims['iat']);
        self::assertSame($fixed + 86400, $this->tokenIssuer->lastClaims['exp']);
    }
}
