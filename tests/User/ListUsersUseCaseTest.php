<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\User;

use NeNeRecords\Auth\User;
use NeNeRecords\User\ListUsersInput;
use NeNeRecords\User\ListUsersUseCase;
use PHPUnit\Framework\TestCase;

/**
 * マルチテナント: ListUsersUseCase の org 絞り込み動作を検証する。
 *
 * - organizationId=null → 全ユーザーを返す（superadmin パス）
 * - organizationId=N   → そのorganizationに属するユーザーのみ返す
 */
final class ListUsersUseCaseTest extends TestCase
{
    private function makeUser(int $id, string $email, string $role, ?int $organizationId = null): User
    {
        return new User(
            id: $id,
            email: $email,
            passwordHash: password_hash('x', PASSWORD_BCRYPT),
            role: $role,
            organizationId: $organizationId,
        );
    }

    // ── null organizationId → 全件 ────────────────────────────────────────────

    public function testNullOrganizationIdReturnsAllUsers(): void
    {
        $users = new InMemoryUserRepository([
            $this->makeUser(1, 'admin@org1.example.com', 'admin', 1),
            $this->makeUser(2, 'editor@org2.example.com', 'editor', 2),
            $this->makeUser(3, 'sa@example.com', 'superadmin', null),
        ]);
        $useCase = new ListUsersUseCase($users);

        $output = $useCase->execute(new ListUsersInput(organizationId: null));

        self::assertCount(3, $output->items);
    }

    public function testNullOrganizationIdWithEmptyRepositoryReturnsEmpty(): void
    {
        $users = new InMemoryUserRepository([]);
        $useCase = new ListUsersUseCase($users);

        $output = $useCase->execute(new ListUsersInput(organizationId: null));

        self::assertCount(0, $output->items);
    }

    // ── int organizationId → org 絞り込み ─────────────────────────────────────

    public function testOrgIdFiltersUsersToOrganization(): void
    {
        $users = new InMemoryUserRepository([
            $this->makeUser(1, 'admin@org1.example.com', 'admin', 1),
            $this->makeUser(2, 'editor@org1.example.com', 'editor', 1),
            $this->makeUser(3, 'admin@org2.example.com', 'admin', 2),
        ]);
        $useCase = new ListUsersUseCase($users);

        $output = $useCase->execute(new ListUsersInput(organizationId: 1));

        self::assertCount(2, $output->items);
        self::assertSame('admin@org1.example.com', $output->items[0]->email);
        self::assertSame('editor@org1.example.com', $output->items[1]->email);
    }

    public function testOrgIdDoesNotReturnUsersFromOtherOrg(): void
    {
        $users = new InMemoryUserRepository([
            $this->makeUser(1, 'admin@org1.example.com', 'admin', 1),
            $this->makeUser(2, 'admin@org2.example.com', 'admin', 2),
        ]);
        $useCase = new ListUsersUseCase($users);

        $output = $useCase->execute(new ListUsersInput(organizationId: 2));

        self::assertCount(1, $output->items);
        self::assertSame('admin@org2.example.com', $output->items[0]->email);
        // org1 のユーザーが含まれないこと
        $emails = array_map(static fn ($u) => $u->email, $output->items);
        self::assertNotContains('admin@org1.example.com', $emails);
    }

    public function testOrgIdWithNoMembersReturnsEmpty(): void
    {
        $users = new InMemoryUserRepository([
            $this->makeUser(1, 'admin@org1.example.com', 'admin', 1),
        ]);
        $useCase = new ListUsersUseCase($users);

        // org 99 にはメンバーがいない
        $output = $useCase->execute(new ListUsersInput(organizationId: 99));

        self::assertCount(0, $output->items);
    }

    // ── 出力フィールドの検証 ────────────────────────────────────────────────────

    public function testOutputItemContainsOrganizationFields(): void
    {
        $users = new InMemoryUserRepository([
            $this->makeUser(1, 'admin@org1.example.com', 'admin', 10),
        ]);
        // org_role を明示的に設定するため create() を使用
        $user = $users->findByEmail('admin@org1.example.com');
        assert($user !== null);

        $useCase = new ListUsersUseCase($users);
        $output = $useCase->execute(new ListUsersInput(organizationId: 10));

        self::assertCount(1, $output->items);
        self::assertSame(10, $output->items[0]->organizationId);
    }

    public function testOutputItemsAreSortedById(): void
    {
        // seed は逆順で挿入してソートを確認
        $users = new InMemoryUserRepository([
            $this->makeUser(3, 'c@org.example.com', 'editor', 5),
            $this->makeUser(1, 'a@org.example.com', 'admin', 5),
            $this->makeUser(2, 'b@org.example.com', 'editor', 5),
        ]);
        $useCase = new ListUsersUseCase($users);

        $output = $useCase->execute(new ListUsersInput(organizationId: 5));

        self::assertSame(1, $output->items[0]->id);
        self::assertSame(2, $output->items[1]->id);
        self::assertSame(3, $output->items[2]->id);
    }
}
