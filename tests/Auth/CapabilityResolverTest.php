<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Auth;

use NeNeRecords\Auth\Capability;
use NeNeRecords\Auth\CapabilityResolver;
use PHPUnit\Framework\TestCase;

final class CapabilityResolverTest extends TestCase
{
    public function testEntityTypeDeleteRequiresManageSchema(): void
    {
        self::assertSame(
            Capability::ManageSchema,
            CapabilityResolver::resolve('/api/v1/entity-types/1', 'DELETE'),
        );
    }

    public function testEntityTypeGetDoesNotRequireCapability(): void
    {
        self::assertNull(CapabilityResolver::resolve('/api/v1/entity-types/1', 'GET'));
    }

    public function testSettingsPutRequiresManageSettings(): void
    {
        self::assertSame(
            Capability::ManageSettings,
            CapabilityResolver::resolve('/api/v1/settings/site_name', 'PUT'),
        );
    }

    public function testSettingsGetRequiresReadSettings(): void
    {
        self::assertSame(
            Capability::ReadSettings,
            CapabilityResolver::resolve('/api/v1/settings', 'GET'),
        );
    }

    public function testEntityCreateRequiresEditContent(): void
    {
        self::assertSame(
            Capability::EditContent,
            CapabilityResolver::resolve('/api/v1/entities', 'POST'),
        );
    }

    public function testArchiveCsvRequiresManageSchema(): void
    {
        self::assertSame(
            Capability::ManageSchema,
            CapabilityResolver::resolve('/api/v1/entity-types/1/archive.csv', 'GET'),
        );
    }
}
