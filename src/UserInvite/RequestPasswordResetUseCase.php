<?php

declare(strict_types=1);

namespace NeNeRecords\UserInvite;

use Nene2\Http\SecureTokenHelper;
use NeNeRecords\Auth\UserRepositoryInterface;
use NeNeRecords\Mail\MailerInterface;
use NeNeRecords\Mail\MailMessage;

final readonly class RequestPasswordResetUseCase implements RequestPasswordResetUseCaseInterface
{
    private const RESET_TTL_SECONDS = 3600; // 1 hour

    public function __construct(
        private UserRepositoryInterface $users,
        private MailerInterface $mailer,
    ) {
    }

    public function execute(RequestPasswordResetInput $input): void
    {
        $user = $this->users->findByEmail($input->email);

        // Always succeed silently to prevent email enumeration
        if ($user === null) {
            return;
        }

        [$rawToken, $tokenHash] = SecureTokenHelper::generateWithHash();
        $expiresAt = time() + self::RESET_TTL_SECONDS;
        $this->users->storePasswordResetToken($user->id, $tokenHash, $expiresAt);

        $resetUrl = rtrim($input->appBaseUrl, '/') . '/admin/reset-password?token=' . $rawToken;

        $this->mailer->send(new MailMessage(
            to: $input->email,
            subject: 'NeNe Records パスワードリセット',
            textBody: <<<TEXT
                パスワードリセットのリクエストを受け付けました。

                以下のリンクをクリックして新しいパスワードを設定してください。
                （有効期限: 1時間）

                {$resetUrl}

                このメールに心当たりのない場合は、無視してください。
                TEXT,
            htmlBody: <<<HTML
                <p>パスワードリセットのリクエストを受け付けました。</p>
                <p>以下のリンクをクリックして新しいパスワードを設定してください。<br>
                （有効期限: 1時間）</p>
                <p><a href="{$resetUrl}">{$resetUrl}</a></p>
                <p>このメールに心当たりのない場合は、無視してください。</p>
                HTML,
        ));
    }
}
