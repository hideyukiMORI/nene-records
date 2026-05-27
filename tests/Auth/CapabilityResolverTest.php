<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Auth;

use NeNeRecords\Auth\Capability;
use NeNeRecords\Auth\CapabilityResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CapabilityResolverTest extends TestCase
{
    // ── Organizations ─────────────────────────────────────────────────────────

    #[DataProvider('provideOrganizationPaths')]
    public function testOrganizationPathsRequireManageOrganizations(string $path, string $method): void
    {
        self::assertSame(Capability::ManageOrganizations, CapabilityResolver::resolve($path, $method));
    }

    /** @return iterable<string, array{string, string}> */
    public static function provideOrganizationPaths(): iterable
    {
        yield 'list GET'   => ['/api/v1/organizations', 'GET'];
        yield 'list POST'  => ['/api/v1/organizations', 'POST'];
        yield 'get by id'  => ['/api/v1/organizations/1', 'GET'];
        yield 'update PUT' => ['/api/v1/organizations/1', 'PUT'];
        yield 'delete'     => ['/api/v1/organizations/1', 'DELETE'];
    }

    // ── Settings ──────────────────────────────────────────────────────────────

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

    public function testSettingsHeadRequiresReadSettings(): void
    {
        self::assertSame(
            Capability::ReadSettings,
            CapabilityResolver::resolve('/api/v1/settings/site_name', 'HEAD'),
        );
    }

    // ── Navigation items ──────────────────────────────────────────────────────

    #[DataProvider('provideNavigationItemMutations')]
    public function testNavigationItemMutationsRequireManageSettings(string $method): void
    {
        self::assertSame(
            Capability::ManageSettings,
            CapabilityResolver::resolve('/api/v1/navigation-items/1', $method),
        );
    }

    /** @return iterable<string, array{string}> */
    public static function provideNavigationItemMutations(): iterable
    {
        yield 'POST'   => ['POST'];
        yield 'PUT'    => ['PUT'];
        yield 'DELETE' => ['DELETE'];
    }

    public function testNavigationItemGetRequiresReadSettings(): void
    {
        self::assertSame(
            Capability::ReadSettings,
            CapabilityResolver::resolve('/api/v1/navigation-items', 'GET'),
        );
    }

    // ── Entity types ──────────────────────────────────────────────────────────

    public function testEntityTypeDeleteRequiresManageSchema(): void
    {
        self::assertSame(
            Capability::ManageSchema,
            CapabilityResolver::resolve('/api/v1/entity-types/1', 'DELETE'),
        );
    }

    public function testEntityTypePostRequiresManageSchema(): void
    {
        self::assertSame(
            Capability::ManageSchema,
            CapabilityResolver::resolve('/api/v1/entity-types', 'POST'),
        );
    }

    public function testEntityTypePutRequiresManageSchema(): void
    {
        self::assertSame(
            Capability::ManageSchema,
            CapabilityResolver::resolve('/api/v1/entity-types/1', 'PUT'),
        );
    }

    public function testEntityTypeGetDoesNotRequireCapability(): void
    {
        self::assertNull(CapabilityResolver::resolve('/api/v1/entity-types/1', 'GET'));
    }

    public function testArchiveCsvRequiresManageSchema(): void
    {
        self::assertSame(
            Capability::ManageSchema,
            CapabilityResolver::resolve('/api/v1/entity-types/1/archive.csv', 'GET'),
        );
    }

    // ── Field defs ────────────────────────────────────────────────────────────

    public function testFieldDefMutationRequiresManageSchema(): void
    {
        self::assertSame(
            Capability::ManageSchema,
            CapabilityResolver::resolve('/api/v1/field-defs/1', 'DELETE'),
        );
    }

    public function testFieldDefGetDoesNotRequireCapability(): void
    {
        self::assertNull(CapabilityResolver::resolve('/api/v1/field-defs', 'GET'));
    }

    // ── Tags ──────────────────────────────────────────────────────────────────

    public function testTagMutationRequiresManageTags(): void
    {
        self::assertSame(
            Capability::ManageTags,
            CapabilityResolver::resolve('/api/v1/tags', 'POST'),
        );
    }

    public function testTagGetDoesNotRequireCapability(): void
    {
        self::assertNull(CapabilityResolver::resolve('/api/v1/tags', 'GET'));
    }

    // ── Media ─────────────────────────────────────────────────────────────────

    public function testMediaDeleteRequiresManageSettings(): void
    {
        self::assertSame(
            Capability::ManageSettings,
            CapabilityResolver::resolve('/api/v1/media/1', 'DELETE'),
        );
    }

    public function testMediaGetRequiresReadSettings(): void
    {
        self::assertSame(
            Capability::ReadSettings,
            CapabilityResolver::resolve('/api/v1/media', 'GET'),
        );
    }

    public function testMediaPostDoesNotRequireCapability(): void
    {
        // Upload is handled by its own auth layer; not explicitly mapped
        self::assertNull(CapabilityResolver::resolve('/api/v1/media', 'POST'));
    }

    // ── Users ─────────────────────────────────────────────────────────────────

    public function testUserMutationRequiresManageSettings(): void
    {
        self::assertSame(
            Capability::ManageSettings,
            CapabilityResolver::resolve('/api/v1/users/1', 'DELETE'),
        );
    }

    public function testUserListGetRequiresManageSettings(): void
    {
        self::assertSame(
            Capability::ManageSettings,
            CapabilityResolver::resolve('/api/v1/users', 'GET'),
        );
    }

    public function testUserGetByIdRequiresManageSettings(): void
    {
        self::assertSame(
            Capability::ManageSettings,
            CapabilityResolver::resolve('/api/v1/users/1', 'GET'),
        );
    }

    public function testUserMePasswordPutDoesNotRequireCapability(): void
    {
        // Self-service password change is accessible to any authenticated user
        self::assertNull(CapabilityResolver::resolve('/api/v1/users/me/password', 'PUT'));
    }

    // ── Admin comments ────────────────────────────────────────────────────────

    public function testAdminCommentsRequireManageSettings(): void
    {
        self::assertSame(
            Capability::ManageSettings,
            CapabilityResolver::resolve('/api/v1/admin/comments', 'GET'),
        );
    }

    public function testAdminCommentApproveRequiresManageSettings(): void
    {
        self::assertSame(
            Capability::ManageSettings,
            CapabilityResolver::resolve('/api/v1/admin/comments/1/approve', 'PATCH'),
        );
    }

    public function testAdminCommentDeleteRequiresManageSettings(): void
    {
        self::assertSame(
            Capability::ManageSettings,
            CapabilityResolver::resolve('/api/v1/admin/comments/1', 'DELETE'),
        );
    }

    // ── Content (entities / fields) ───────────────────────────────────────────

    public function testEntityCreateRequiresEditContent(): void
    {
        self::assertSame(
            Capability::EditContent,
            CapabilityResolver::resolve('/api/v1/entities', 'POST'),
        );
    }

    #[DataProvider('provideContentMutationPaths')]
    public function testContentMutationPathsRequireEditContent(string $path): void
    {
        self::assertSame(Capability::EditContent, CapabilityResolver::resolve($path, 'POST'));
        self::assertSame(Capability::EditContent, CapabilityResolver::resolve($path . '/1', 'PUT'));
        self::assertSame(Capability::EditContent, CapabilityResolver::resolve($path . '/1', 'DELETE'));
    }

    /** @return iterable<string, array{string}> */
    public static function provideContentMutationPaths(): iterable
    {
        yield 'text-fields'     => ['/api/v1/text-fields'];
        yield 'int-fields'      => ['/api/v1/int-fields'];
        yield 'enum-fields'     => ['/api/v1/enum-fields'];
        yield 'bool-fields'     => ['/api/v1/bool-fields'];
        yield 'datetime-fields' => ['/api/v1/datetime-fields'];
    }

    public function testEntityGetDoesNotRequireCapability(): void
    {
        self::assertNull(CapabilityResolver::resolve('/api/v1/entities/1', 'GET'));
    }

    // ── Public endpoints ──────────────────────────────────────────────────────

    public function testPublicRecordPathDoesNotRequireCapability(): void
    {
        self::assertNull(CapabilityResolver::resolve('/api/v1/public/records/article/my-post', 'GET'));
    }

    public function testUnknownPathDoesNotRequireCapability(): void
    {
        self::assertNull(CapabilityResolver::resolve('/api/v1/unknown', 'GET'));
    }
}
