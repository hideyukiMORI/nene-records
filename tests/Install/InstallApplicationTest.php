<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Install;

use Nene2\Http\RequestScopedHolder;
use NeNeRecords\Install\InstallApplication;
use NeNeRecords\Install\InstallConfig;
use NeNeRecords\Organization\CreateOrganizationUseCase;
use NeNeRecords\Organization\DefaultContentTypeSeederInterface;
use NeNeRecords\Setting\UpdateSettingInput;
use NeNeRecords\Setting\UpdateSettingOutput;
use NeNeRecords\Setting\UpdateSettingUseCaseInterface;
use NeNeRecords\Tests\Organization\InMemoryOrganizationRepository;
use NeNeRecords\Tests\Organization\RecordingDefaultSettingDefsSeeder;
use NeNeRecords\Tests\User\InMemoryUserRepository;
use NeNeRecords\User\CreateUserUseCase;
use PHPUnit\Framework\TestCase;

final class InstallApplicationTest extends TestCase
{
    private function app(
        InMemoryOrganizationRepository $orgs,
        InMemoryUserRepository $users,
        UpdateSettingUseCaseInterface $updateSetting,
    ): InstallApplication {
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
            $updateSetting,
        );
    }

    private function recordingUpdateSetting(): RecordingUpdateSetting
    {
        return new RecordingUpdateSetting();
    }

    public function testCreatesOrganizationAndAdminOnFreshInstall(): void
    {
        $orgs = new InMemoryOrganizationRepository();
        $users = new InMemoryUserRepository([]);
        $updateSetting = $this->recordingUpdateSetting();

        $result = $this->app($orgs, $users, $updateSetting)->install(
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

    public function testReflectsEnteredNameIntoSiteNameSettingOnFreshInstall(): void
    {
        $orgs = new InMemoryOrganizationRepository();
        $users = new InMemoryUserRepository([]);
        $updateSetting = $this->recordingUpdateSetting();

        $this->app($orgs, $users, $updateSetting)->install(
            new InstallConfig('Acme Publishing', 'acme', 'admin@acme.test', 'secret-password'),
        );

        self::assertCount(1, $updateSetting->inputs);
        self::assertSame('site_name', $updateSetting->inputs[0]->settingKey);
        self::assertSame('Acme Publishing', $updateSetting->inputs[0]->value);
    }

    public function testIsIdempotentOnReRun(): void
    {
        $orgs = new InMemoryOrganizationRepository();
        $users = new InMemoryUserRepository([]);
        $config = new InstallConfig('Acme', 'acme', 'admin@acme.test', 'secret-password');

        $this->app($orgs, $users, $this->recordingUpdateSetting())->install($config);

        $secondUpdateSetting = $this->recordingUpdateSetting();
        $second = $this->app($orgs, $users, $secondUpdateSetting)->install($config);

        self::assertFalse($second->organizationCreated);
        self::assertFalse($second->adminCreated);
        self::assertSame(1, $orgs->count());
        // The org already exists, so the re-run must not overwrite a possibly
        // user-customized site_name.
        self::assertSame([], $secondUpdateSetting->inputs);
    }
}

/** Records every setting write so tests can assert what the installer reflected. */
final class RecordingUpdateSetting implements UpdateSettingUseCaseInterface
{
    /** @var list<UpdateSettingInput> */
    public array $inputs = [];

    public function execute(UpdateSettingInput $input): UpdateSettingOutput
    {
        $this->inputs[] = $input;

        return new UpdateSettingOutput($input->settingKey, $input->value, '2026-07-05 00:00:00');
    }
}
