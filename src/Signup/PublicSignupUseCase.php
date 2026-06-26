<?php

declare(strict_types=1);

namespace NeNeRecords\Signup;

use Nene2\Http\RequestScopedHolder;
use Nene2\Http\SecureTokenHelper;
use NeNeRecords\Auth\LoginInput;
use NeNeRecords\Auth\LoginUseCase;
use NeNeRecords\Auth\Role;
use NeNeRecords\Auth\UserRepositoryInterface;
use NeNeRecords\Mail\MailerInterface;
use NeNeRecords\Mail\MailMessage;
use NeNeRecords\Organization\CreateOrganizationInput;
use NeNeRecords\Organization\CreateOrganizationUseCaseInterface;
use NeNeRecords\User\CreateUserInput;
use NeNeRecords\User\CreateUserUseCaseInterface;
use Throwable;

/**
 * Public self-serve signup for the subdomain SaaS: provisions a new tenant in one
 * step — organization (active, free plan, default content types auto-seeded) plus
 * its admin user — then logs that admin straight in so the URL "register → use" is
 * a single round trip.
 *
 * Mirrors {@see \NeNeRecords\Install\InstallApplication} but for an
 * untrusted public caller: the org is always active/free, never superadmin, and
 * the slug has already been format/reservation-checked by the handler. Slug and
 * email uniqueness are enforced by the underlying use cases (which raise
 * OrganizationSlugConflictException / UserEmailConflictException → 409).
 */
final readonly class PublicSignupUseCase implements PublicSignupUseCaseInterface
{
    private const VERIFICATION_TTL_SECONDS = 24 * 3600;

    /**
     * @param RequestScopedHolder<int> $orgHolder
     */
    public function __construct(
        private CreateOrganizationUseCaseInterface $createOrganization,
        private CreateUserUseCaseInterface $createUser,
        private LoginUseCase $login,
        private RequestScopedHolder $orgHolder,
        private UserRepositoryInterface $users,
        private MailerInterface $mailer,
    ) {
    }

    public function execute(PublicSignupInput $input): PublicSignupOutput
    {
        // 1. Organization (active + free + seeded). 409 on slug conflict.
        $org = $this->createOrganization->execute(new CreateOrganizationInput(
            name: $input->organizationName,
            slug: $input->slug,
        ));

        // 2. Scope subsequent writes (and the admin's email-uniqueness check) to it.
        $this->orgHolder->set($org->id);

        // 3. Admin user. 409 on email conflict (unlikely within a brand-new org).
        $this->createUser->execute(new CreateUserInput(
            email: $input->email,
            password: $input->password,
            role: Role::Admin->value,
            organizationId: $org->id,
        ));

        // 4. Auto-login through the normal login path (same token + TTL).
        $session = $this->login->execute(new LoginInput(email: $input->email, password: $input->password));

        // 5. Send the email-verification link. Best-effort: onboarding is not blocked
        //    if mail hiccups — the admin can resend, and unverified state is a soft gate.
        $this->sendVerificationEmail($input->email, $input->verifyUrlBase);

        return new PublicSignupOutput(
            token: $session->token,
            expiresAt: $session->expiresAt,
            organizationId: $org->id,
            slug: $org->slug,
            email: $session->email,
            role: $session->role,
        );
    }

    private function sendVerificationEmail(string $email, string $verifyUrlBase): void
    {
        try {
            $user = $this->users->findByEmail($email);
            if ($user === null) {
                return;
            }

            [$rawToken, $tokenHash] = SecureTokenHelper::generateWithHash();
            $this->users->storeEmailVerification(
                $user->id,
                $email,
                $tokenHash,
                time() + self::VERIFICATION_TTL_SECONDS,
            );

            $verifyUrl = rtrim($verifyUrlBase, '/') . '/verify-email?token=' . $rawToken;

            $this->mailer->send(new MailMessage(
                to: $email,
                subject: 'NeNe Records — メールアドレスの確認',
                textBody: "ご登録ありがとうございます。\n\n下記のリンクからメールアドレスを確認してください（24時間有効）。\n{$verifyUrl}\n\n心当たりがない場合はこのメールを無視してください。",
                htmlBody: "<p>ご登録ありがとうございます。</p><p>下記のボタンからメールアドレスを確認してください（24時間有効）。</p><p><a href=\"{$verifyUrl}\">メールアドレスを確認</a></p><p>心当たりがない場合はこのメールを無視してください。</p>",
            ));
        } catch (Throwable) {
            // Swallow: the tenant + admin already exist and are signed in.
        }
    }
}
