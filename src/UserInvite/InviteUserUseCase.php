<?php

declare(strict_types=1);

namespace NeNeRecords\UserInvite;

use Nene2\Http\SecureTokenHelper;
use NeNeRecords\Auth\Role;
use NeNeRecords\Auth\UserRepositoryInterface;
use NeNeRecords\Mail\MailerInterface;
use NeNeRecords\Mail\MailMessage;
use NeNeRecords\User\InvalidUserRoleException;
use NeNeRecords\User\UserEmailConflictException;

final readonly class InviteUserUseCase implements InviteUserUseCaseInterface
{
    private const INVITE_TTL_SECONDS = 72 * 3600; // 72 hours

    public function __construct(
        private UserRepositoryInterface $users,
        private MailerInterface $mailer,
    ) {
    }

    public function execute(InviteUserInput $input): InviteUserOutput
    {
        if (Role::tryFrom($input->role) === null) {
            throw new InvalidUserRoleException($input->role);
        }

        $existing = $this->users->findByEmail($input->email);

        if ($existing !== null) {
            throw new UserEmailConflictException($input->email);
        }

        // Create as invited user with a temporary random password (never usable without the invite)
        $tempHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT);
        $user = $this->users->create($input->email, $tempHash, $input->role);

        [$rawToken, $tokenHash] = SecureTokenHelper::generateWithHash();
        $expiresAt = time() + self::INVITE_TTL_SECONDS;
        $this->users->storeInviteToken($user->id, $tokenHash, $expiresAt);

        $acceptUrl = rtrim($input->appBaseUrl, '/') . '/admin/accept-invite?token=' . $rawToken;

        $this->mailer->send(new MailMessage(
            to: $input->email,
            subject: 'NeNe Records へのご招待',
            textBody: <<<TEXT
                NeNe Records 管理画面にご招待されました。

                以下のリンクをクリックしてパスワードを設定し、アカウントを有効化してください。
                （有効期限: 72時間）

                {$acceptUrl}

                このメールに心当たりのない場合は、無視してください。
                TEXT,
            htmlBody: <<<HTML
                <p>NeNe Records 管理画面にご招待されました。</p>
                <p>以下のリンクをクリックしてパスワードを設定し、アカウントを有効化してください。<br>
                （有効期限: 72時間）</p>
                <p><a href="{$acceptUrl}">{$acceptUrl}</a></p>
                <p>このメールに心当たりのない場合は、無視してください。</p>
                HTML,
        ));

        return new InviteUserOutput(
            id: $user->id,
            email: $user->email,
            role: $user->role,
            status: 'invited',
        );
    }
}
