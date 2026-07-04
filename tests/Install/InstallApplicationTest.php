<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Install;

use Nene2\Http\RequestScopedHolder;
use NeNeRecords\Install\InstallApplication;
use NeNeRecords\Install\InstallConfig;
use NeNeRecords\Organization\CreateOrganizationUseCase;
use NeNeRecords\Organization\DefaultContentTypeSeederInterface;
use NeNeRecords\Tests\Organization\InMemoryOrganizationRepository;
use NeNeRecords\Tests\Organization\RecordingDefaultSettingDefsSeeder;
use NeNeRecords\Tests\User\InMemoryUserRepository;
use NeNeRecords\User\CreateUserUseCase;
use PHPUnit\Framework\TestCase;

final class InstallApplicationTest extends TestCase
{
    private function app(InMemoryOrganizationRepository $orgs, InMemoryUserRepository $users): InstallApplication
    {
        $seeder = new class () implements DefaultContentTypeSeederInterface {
            public int $seededFor = 0;

            public function seed(int $organizationId): void
            {
                $this->seededFor = $organizationId;
            }
        };

        /** @var RequestScopedHolder<int> $holder */
        $holder = new RequestScopedHolder();

        return new InstallApplication(
            new CreateOrganizationUseCase($orgs, $seeder, new RecordingDefaultSettingDefsSeeder()),
            $orgs,
            new CreateUserUseCase($users),
            $holder,
        );
    }

    public function testCreatesOrganizationAndAdminOnFreshInstall(): void
    {
        $orgs = new InMemoryOrganizationRepository();
        $users = new InMemoryUserRepository([]);

        $result = $this->app($orgs, $users)->install(
            new InstallConfig('Acme', 'acme', 'admin@acme.test', 'secret-password'),
        );

        self::assertTrue($result->organizationCreated);
        self::assertTrue($result->adminCreated);
        self::assertSame('acme', $result->organizationSlug);
        self::assertNotNull($orgs->findBySlug('acme'));

        $admin = $users->findByEmail('admin@acme.test');
        self::assertNotNull($admin);
        self::assertSame('admin', $admin->role);
    }

    public function testIsIdempotentOnReRun(): void
    {
        $orgs = new InMemoryOrganizationRepository();
        $users = new InMemoryUserRepository([]);
        $config = new InstallConfig('Acme', 'acme', 'admin@acme.test', 'secret-password');

        $this->app($orgs, $users)->install($config);
        $second = $this->app($orgs, $users)->install($config);

        self::assertFalse($second->organizationCreated);
        self::assertFalse($second->adminCreated);
        self::assertSame(1, $orgs->count());
    }
}
