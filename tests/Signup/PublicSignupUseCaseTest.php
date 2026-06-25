<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Signup;

use Nene2\Auth\TokenIssuerInterface;
use Nene2\Http\RequestScopedHolder;
use NeNeRecords\Auth\LoginUseCase;
use NeNeRecords\Organization\CreateOrganizationUseCase;
use NeNeRecords\Organization\OrganizationSlugConflictException;
use NeNeRecords\Signup\PublicSignupInput;
use NeNeRecords\Signup\PublicSignupUseCase;
use NeNeRecords\Tests\Mail\RecordingMailer;
use NeNeRecords\Tests\Organization\InMemoryOrganizationRepository;
use NeNeRecords\Tests\Organization\RecordingDefaultContentTypeSeeder;
use NeNeRecords\Tests\User\InMemoryUserRepository;
use NeNeRecords\User\CreateUserUseCase;
use PHPUnit\Framework\TestCase;

final class PublicSignupUseCaseTest extends TestCase
{
    private InMemoryOrganizationRepository $orgs;
    private InMemoryUserRepository $users;
    private RecordingDefaultContentTypeSeeder $seeder;
    private RecordingMailer $mailer;

    private function useCase(): PublicSignupUseCase
    {
        $this->orgs = new InMemoryOrganizationRepository();
        $this->users = new InMemoryUserRepository([]);
        $this->seeder = new RecordingDefaultContentTypeSeeder();
        $this->mailer = new RecordingMailer();

        $tokenIssuer = new class () implements TokenIssuerInterface {
            /** @param array<string, mixed> $claims */
            public function issue(array $claims): string
            {
                return 'tok-' . (is_string($claims['sub'] ?? null) ? $claims['sub'] : '');
            }
        };

        /** @var RequestScopedHolder<int> $holder */
        $holder = new RequestScopedHolder();

        return new PublicSignupUseCase(
            new CreateOrganizationUseCase($this->orgs, $this->seeder),
            new CreateUserUseCase($this->users),
            new LoginUseCase($this->users, $tokenIssuer),
            $holder,
            $this->users,
            $this->mailer,
        );
    }

    public function testProvisionsTenantAndAutoLogsIn(): void
    {
        $output = $this->useCase()->execute(
            new PublicSignupInput('My Shop', 'my-shop', 'owner@shop.test', 'secret-password'),
        );

        // Organization created (active, seeded) …
        $org = $this->orgs->findBySlug('my-shop');
        self::assertNotNull($org);
        self::assertTrue($org->isActive);
        self::assertSame('free', $org->plan);
        self::assertSame([$org->id], $this->seeder->seededOrgIds);

        // … admin user created …
        $admin = $this->users->findByEmail('owner@shop.test');
        self::assertNotNull($admin);
        self::assertSame('admin', $admin->role);

        // … and signed straight in.
        self::assertSame('tok-owner@shop.test', $output->token);
        self::assertSame('my-shop', $output->slug);
        self::assertSame('admin', $output->role);
        self::assertSame($org->id, $output->organizationId);

        // … and a verification email was queued to the new admin (unverified).
        self::assertCount(1, $this->mailer->sent);
        self::assertSame('owner@shop.test', $this->mailer->sent[0]->to);
        self::assertStringContainsString('/verify-email?token=', $this->mailer->sent[0]->htmlBody);
        $admin = $this->users->findByEmail('owner@shop.test');
        self::assertNotNull($admin);
        self::assertNull($admin->emailVerifiedAt); // starts unverified
    }

    public function testDuplicateSlugIsRejected(): void
    {
        $useCase = $this->useCase();
        $useCase->execute(new PublicSignupInput('First', 'taken', 'a@x.test', 'secret-password'));

        $this->expectException(OrganizationSlugConflictException::class);
        $useCase->execute(new PublicSignupInput('Second', 'taken', 'b@y.test', 'secret-password'));
    }
}
