<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\SystemConfig;

use Nene2\Validation\ValidationException;
use NeNeRecords\SystemConfig\GetSystemConfigUseCase;
use NeNeRecords\SystemConfig\UpdateSystemConfigInput;
use NeNeRecords\SystemConfig\UpdateSystemConfigUseCase;
use PHPUnit\Framework\TestCase;

final class SystemConfigUseCaseTest extends TestCase
{
    public function testGetReturnsDefaultsWhenRepositoryIsEmpty(): void
    {
        $repo = new InMemorySystemConfigRepository();
        $useCase = new GetSystemConfigUseCase($repo);

        $output = $useCase->execute();

        self::assertSame('single', $output->tenantResolutionMode);
        self::assertSame('', $output->tenantOrgSlug);
        self::assertSame('localhost', $output->tenantBaseDomain);
    }

    public function testGetReturnsStoredValuesWhenSet(): void
    {
        $repo = new InMemorySystemConfigRepository([
            'tenant_resolution_mode' => 'subdomain',
            'tenant_org_slug' => 'acme',
            'tenant_base_domain' => 'example.com',
        ]);

        $useCase = new GetSystemConfigUseCase($repo);

        $output = $useCase->execute();

        self::assertSame('subdomain', $output->tenantResolutionMode);
        self::assertSame('acme', $output->tenantOrgSlug);
        self::assertSame('example.com', $output->tenantBaseDomain);
    }

    public function testUpdateSetsTenantResolutionMode(): void
    {
        $repo = new InMemorySystemConfigRepository();
        $useCase = new UpdateSystemConfigUseCase($repo);

        $output = $useCase->execute(new UpdateSystemConfigInput(
            tenantResolutionMode: 'subdomain',
            tenantOrgSlug: null,
            tenantBaseDomain: null,
        ));

        self::assertSame('subdomain', $output->tenantResolutionMode);
    }

    public function testUpdateSetsOptionalFieldsWhenProvided(): void
    {
        $repo = new InMemorySystemConfigRepository();
        $useCase = new UpdateSystemConfigUseCase($repo);

        $output = $useCase->execute(new UpdateSystemConfigInput(
            tenantResolutionMode: 'path',
            tenantOrgSlug: 'my-org',
            tenantBaseDomain: 'mysite.io',
        ));

        self::assertSame('path', $output->tenantResolutionMode);
        self::assertSame('my-org', $output->tenantOrgSlug);
        self::assertSame('mysite.io', $output->tenantBaseDomain);
    }

    public function testUpdateThrowsValidationExceptionForInvalidMode(): void
    {
        $repo = new InMemorySystemConfigRepository();
        $useCase = new UpdateSystemConfigUseCase($repo);

        $this->expectException(ValidationException::class);

        $useCase->execute(new UpdateSystemConfigInput(
            tenantResolutionMode: 'invalid_mode',
            tenantOrgSlug: null,
            tenantBaseDomain: null,
        ));
    }
}
