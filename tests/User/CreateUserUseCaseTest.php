<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\User;

use NeNeRecords\Auth\User;
use NeNeRecords\User\CreateUserInput;
use NeNeRecords\User\CreateUserUseCase;
use NeNeRecords\User\InvalidUserRoleException;
use NeNeRecords\User\UserEmailConflictException;
use PHPUnit\Framework\TestCase;

/**
 * マルチテナント: CreateUserUseCase の org 割当動作を検証する。
 *
 * - organizationId / orgRole が User エンティティに正しく設定されること
 * - orgRole が省略された場合は role と同値になること
 * - superadmin ロールは作成できないこと
 */
final class CreateUserUseCaseTest extends TestCase
{
    // ── org 割当なしで作成 ────────────────────────────────────────────────────

    public function testCreatesUserWithoutOrganization(): void
    {
        $users = new InMemoryUserRepository([]);
        $useCase = new CreateUserUseCase($users);

        $output = $useCase->execute(new CreateUserInput(
            email: 'new@example.com',
            password: 'password123',
            role: 'admin',
        ));

        self::assertSame('new@example.com', $output->email);
        self::assertSame('admin', $output->role);

        $created = $users->findByEmail('new@example.com');
        self::assertNotNull($created);
        self::assertNull($created->organizationId);
    }

    // ── org 割当あり ──────────────────────────────────────────────────────────

    public function testCreatesUserWithOrganizationId(): void
    {
        $users = new InMemoryUserRepository([]);
        $useCase = new CreateUserUseCase($users);

        $useCase->execute(new CreateUserInput(
            email: 'member@org.example.com',
            password: 'password123',
            role: 'admin',
            organizationId: 42,
        ));

        $created = $users->findByEmail('member@org.example.com');
        self::assertNotNull($created);
        self::assertSame(42, $created->organizationId);
    }

    /**
     * orgRole が省略された場合は role と同値に設定される
     */
    public function testCreatedUserOrgRoleDefaultsToRole(): void
    {
        $users = new InMemoryUserRepository([]);
        $useCase = new CreateUserUseCase($users);

        $useCase->execute(new CreateUserInput(
            email: 'editor@org.example.com',
            password: 'password123',
            role: 'editor',
            organizationId: 10,
        ));

        $created = $users->findByEmail('editor@org.example.com');
        self::assertNotNull($created);
        self::assertSame('editor', $created->orgRole);
    }

    /**
     * orgRole を明示的に指定した場合はその値が使われる
     */
    public function testCreatesUserWithExplicitOrgRole(): void
    {
        $users = new InMemoryUserRepository([]);
        $useCase = new CreateUserUseCase($users);

        $useCase->execute(new CreateUserInput(
            email: 'admin2@org.example.com',
            password: 'password123',
            role: 'admin',
            organizationId: 10,
            orgRole: 'billing_admin',
        ));

        $created = $users->findByEmail('admin2@org.example.com');
        self::assertNotNull($created);
        self::assertSame('billing_admin', $created->orgRole);
    }

    // ── 組織間の分離 ──────────────────────────────────────────────────────────

    /**
     * 別の org に割り当てられたユーザーは org1 の ListByOrganizationId に出ない
     */
    public function testUserAssignedToOrg2IsNotReturnedForOrg1(): void
    {
        $users = new InMemoryUserRepository([]);
        $useCase = new CreateUserUseCase($users);

        $useCase->execute(new CreateUserInput(
            email: 'org1admin@example.com',
            password: 'password123',
            role: 'admin',
            organizationId: 1,
        ));
        $useCase->execute(new CreateUserInput(
            email: 'org2admin@example.com',
            password: 'password123',
            role: 'admin',
            organizationId: 2,
        ));

        $org1Users = $users->listByOrganizationId(1);

        self::assertCount(1, $org1Users);
        self::assertSame('org1admin@example.com', $org1Users[0]->email);
    }

    // ── エラーケース ──────────────────────────────────────────────────────────

    public function testThrowsInvalidUserRoleExceptionForSuperadminRole(): void
    {
        $users = new InMemoryUserRepository([]);
        $useCase = new CreateUserUseCase($users);

        $this->expectException(InvalidUserRoleException::class);

        $useCase->execute(new CreateUserInput(
            email: 'sa@example.com',
            password: 'password123',
            role: 'superadmin',
            organizationId: 1,
        ));
    }

    public function testThrowsUserEmailConflictExceptionForDuplicateEmail(): void
    {
        $existing = new User(
            id: 1,
            email: 'taken@example.com',
            passwordHash: password_hash('x', PASSWORD_BCRYPT),
            role: 'admin',
            organizationId: 1,
        );
        $users = new InMemoryUserRepository([$existing]);
        $useCase = new CreateUserUseCase($users);

        $this->expectException(UserEmailConflictException::class);

        $useCase->execute(new CreateUserInput(
            email: 'taken@example.com',
            password: 'password123',
            role: 'editor',
            organizationId: 2, // 別の org でも email 重複はエラー
        ));
    }
}
