<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use Nene2\Http\SecureTokenHelper;
use NeNeRecords\Auth\UserRepositoryInterface;
use NeNeRecords\Mail\MailerInterface;
use NeNeRecords\Mail\MailMessage;

/**
 * Starts an email change: instead of updating the address immediately, it stores the
 * new address as pending and emails a verification link to that address (#283).
 * The change only takes effect once the recipient confirms via {@see VerifyEmailChangeUseCase}.
 */
final readonly class ChangeEmailUseCase implements ChangeEmailUseCaseInterface
{
    private const VERIFICATION_TTL_SECONDS = 86400; // 24 hours

    public function __construct(
        private UserRepositoryInterface $users,
        private MailerInterface $mailer,
    ) {
    }

    public function execute(ChangeEmailInput $input): void
    {
        $user = $this->users->findById($input->userId);

        if ($user === null) {
            throw new UserNotFoundException($input->userId);
        }

        if ($user->email === $input->email) {
            return;
        }

        $existing = $this->users->findByEmail($input->email);

        if ($existing !== null) {
            throw new UserEmailConflictException($input->email);
        }

        [$rawToken, $tokenHash] = SecureTokenHelper::generateWithHash();
        $expiresAt = time() + self::VERIFICATION_TTL_SECONDS;
        $this->users->storeEmailVerification($input->userId, $input->email, $tokenHash, $expiresAt);

        $verifyUrl = rtrim($input->appBaseUrl, '/') . '/admin/verify-email?token=' . $rawToken;

        $this->mailer->send(new MailMessage(
            to: $input->email,
            subject: 'NeNe Records メールアドレス変更の確認',
            textBody: <<<TEXT
                メールアドレス変更のリクエストを受け付けました。

                以下のリンクをクリックして、この新しいメールアドレスを確認してください。
                （有効期限: 24時間）

                {$verifyUrl}

                このメールに心当たりのない場合は、無視してください。変更は反映されません。
                TEXT,
            htmlBody: <<<HTML
                <p>メールアドレス変更のリクエストを受け付けました。</p>
                <p>以下のリンクをクリックして、この新しいメールアドレスを確認してください。<br>
                （有効期限: 24時間）</p>
                <p><a href="{$verifyUrl}">{$verifyUrl}</a></p>
                <p>このメールに心当たりのない場合は、無視してください。変更は反映されません。</p>
                HTML,
        ));
    }
}
