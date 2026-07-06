<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\UserInvite;

use Nene2\Http\UtcClock;
use NeNeRecords\Tests\User\InMemoryUserRepository;
use NeNeRecords\UserInvite\InviteUserInput;
use NeNeRecords\UserInvite\InviteUserUseCase;
use PHPUnit\Framework\TestCase;

/**
 * マルチテナント: InviteUserUseCase の org 割当動作を検証する。
 *
 * - 招待時に organizationId / orgRole が User エンティティに伝播すること
 * - orgRole が省略された場合は role と同値になること
 */
final class InviteUserMultitenancyTest extends TestCase
{
    /**
     * 招待ユーザーに organizationId が正しく設定される
     */
    public function testInvitedUserGetsOrganizationId(): void
    {
        $users = new InMemoryUserRepository([]);
        $mailer = new NullMailer();
        $useCase = new InviteUserUseCase($users, $mailer, new UtcClock());

        $useCase->execute(new InviteUserInput(
            email: 'invite@org.example.com',
            role: 'editor',
            appBaseUrl: 'https://nene-records.example.com',
            organizationId: 55,
        ));

        $created = $users->findByEmail('invite@org.example.com');
        self::assertNotNull($created);
        self::assertSame(55, $created->organizationId);
    }

    /**
     * orgRole が省略された場合、招待ユーザーの orgRole は role と同値になる
     */
    public function testInvitedUserOrgRoleDefaultsToRole(): void
    {
        $users = new InMemoryUserRepository([]);
        $mailer = new NullMailer();
        $useCase = new InviteUserUseCase($users, $mailer, new UtcClock());

        $useCase->execute(new InviteUserInput(
            email: 'editor@org.example.com',
            role: 'editor',
            appBaseUrl: 'https://nene-records.example.com',
            organizationId: 10,
        ));

        $created = $users->findByEmail('editor@org.example.com');
        self::assertNotNull($created);
        self::assertSame('editor', $created->orgRole);
    }

    /**
     * orgRole を明示的に指定した場合はその値が使われる
     */
    public function testInviteWithExplicitOrgRoleUsesSpecifiedValue(): void
    {
        $users = new InMemoryUserRepository([]);
        $mailer = new NullMailer();
        $useCase = new InviteUserUseCase($users, $mailer, new UtcClock());

        $useCase->execute(new InviteUserInput(
            email: 'lead@org.example.com',
            role: 'admin',
            appBaseUrl: 'https://nene-records.example.com',
            organizationId: 10,
            orgRole: 'lead_editor',
        ));

        $created = $users->findByEmail('lead@org.example.com');
        self::assertNotNull($created);
        self::assertSame('lead_editor', $created->orgRole);
    }

    /**
     * 招待ユーザーは status=invited で作成される
     */
    public function testInvitedUserHasInvitedStatus(): void
    {
        $users = new InMemoryUserRepository([]);
        $mailer = new NullMailer();
        $useCase = new InviteUserUseCase($users, $mailer, new UtcClock());

        $output = $useCase->execute(new InviteUserInput(
            email: 'pending@org.example.com',
            role: 'admin',
            appBaseUrl: 'https://nene-records.example.com',
            organizationId: 5,
        ));

        self::assertSame('invited', $output->status);

        $created = $users->findByEmail('pending@org.example.com');
        self::assertNotNull($created);
        self::assertNotNull($created->inviteTokenHash);
    }

    /**
     * 招待後に listByOrganizationId で当該 org のユーザーとして取得できる
     */
    public function testInvitedUserAppearsInOrgUserList(): void
    {
        $users = new InMemoryUserRepository([]);
        $mailer = new NullMailer();
        $useCase = new InviteUserUseCase($users, $mailer, new UtcClock());

        $useCase->execute(new InviteUserInput(
            email: 'newmember@org.example.com',
            role: 'editor',
            appBaseUrl: 'https://nene-records.example.com',
            organizationId: 20,
        ));

        $orgUsers = $users->listByOrganizationId(20);
        self::assertCount(1, $orgUsers);
        self::assertSame('newmember@org.example.com', $orgUsers[0]->email);
    }

    /**
     * 招待ユーザーは別の org の listByOrganizationId には出ない
     */
    public function testInvitedUserDoesNotAppearInOtherOrgList(): void
    {
        $users = new InMemoryUserRepository([]);
        $mailer = new NullMailer();
        $useCase = new InviteUserUseCase($users, $mailer, new UtcClock());

        $useCase->execute(new InviteUserInput(
            email: 'org3member@example.com',
            role: 'editor',
            appBaseUrl: 'https://nene-records.example.com',
            organizationId: 3,
        ));

        // org 4 のユーザー一覧は空
        $orgUsers = $users->listByOrganizationId(4);
        self::assertCount(0, $orgUsers);
    }
}
