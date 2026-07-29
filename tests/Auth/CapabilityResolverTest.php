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

    // ── Superadmin console ────────────────────────────────────────────────────

    #[DataProvider('provideSuperadminPaths')]
    public function testSuperadminPathsRequireManageOrganizations(string $path, string $method): void
    {
        self::assertSame(Capability::ManageOrganizations, CapabilityResolver::resolve($path, $method));
    }

    /** @return iterable<string, array{string, string}> */
    public static function provideSuperadminPaths(): iterable
    {
        // GET export must be gated too — it reads every tenant's data. See #797.
        yield 'org export GET'         => ['/api/v1/superadmin/organizations/1/export', 'GET'];
        yield 'org import POST'        => ['/api/v1/superadmin/organizations/1/import', 'POST'];
        yield 'data-migration status'  => ['/api/v1/superadmin/data-migration/status', 'GET'];
        yield 'data-migration assign'  => ['/api/v1/superadmin/data-migration/assign-org', 'POST'];
        yield 'system-config GET'      => ['/api/v1/superadmin/system-config', 'GET'];
        yield 'system-config PATCH'    => ['/api/v1/superadmin/system-config', 'PATCH'];
    }

    // ── Account ─────────────────────────────────────────────────────────────────

    public function testAccountRequiresManageAccount(): void
    {
        self::assertSame(
            Capability::ManageAccount,
            CapabilityResolver::resolve('/api/v1/account', 'GET'),
        );
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

    // ── GET / HEAD parity (#1023) ─────────────────────────────────────────────

    /**
     * HEAD must resolve to the same capability as GET, everywhere. It returns no body,
     * but it returns the status code, so an unauthorized HEAD is an existence oracle
     * (200 vs 404 on `/users/{id}`) and, where the server sets it, a size oracle.
     *
     * Table-driven and covering every branch of resolve(), not just the one that was
     * broken: the defect was a habit (`$method === 'GET'`) rather than a one-off, and
     * the next person adding a branch will copy whatever is nearest.
     */
    #[DataProvider('provideAuthorizedPaths')]
    public function testHeadResolvesToTheSameCapabilityAsGet(string $path): void
    {
        self::assertSame(
            CapabilityResolver::resolve($path, 'GET'),
            CapabilityResolver::resolve($path, 'HEAD'),
            sprintf('GET and HEAD must require the same capability for %s', $path),
        );
    }

    /** @return iterable<string, array{string}> */
    public static function provideAuthorizedPaths(): iterable
    {
        yield 'superadmin'          => ['/api/v1/superadmin/export'];
        yield 'organizations'       => ['/api/v1/organizations'];
        yield 'organization by id'  => ['/api/v1/organizations/2'];
        yield 'account'             => ['/api/v1/account'];
        yield 'settings'            => ['/api/v1/settings'];
        yield 'navigation-items'    => ['/api/v1/navigation-items'];
        yield 'widgets'             => ['/api/v1/widgets'];
        yield 'entity-types'        => ['/api/v1/entity-types'];
        yield 'archive csv'         => ['/api/v1/entity-types/5/archive.csv'];
        yield 'field-defs'          => ['/api/v1/field-defs'];
        yield 'tags'                => ['/api/v1/tags'];
        yield 'media'               => ['/api/v1/media'];
        yield 'media by id'         => ['/api/v1/media/9'];
        yield 'users list'          => ['/api/v1/users'];
        yield 'user by id'          => ['/api/v1/users/3'];
        yield 'user me'             => ['/api/v1/users/me'];
        yield 'own password'        => ['/api/v1/users/me/password'];
        yield 'admin comments'      => ['/api/v1/admin/comments'];
        yield 'entities'            => ['/api/v1/entities'];
        yield 'text-fields'         => ['/api/v1/text-fields'];
        yield 'public records'      => ['/api/v1/public/records/article/my-post'];
        yield 'unknown'             => ['/api/v1/unknown'];
    }

    /**
     * The specific regression: reading the user list is admin-only, and used to be
     * admin-only for GET alone.
     */
    public function testUserListReadRequiresManageSettingsForHeadToo(): void
    {
        self::assertSame(Capability::ManageSettings, CapabilityResolver::resolve('/api/v1/users', 'HEAD'));
        self::assertSame(Capability::ManageSettings, CapabilityResolver::resolve('/api/v1/users/3', 'HEAD'));
    }

    /**
     * Parity must not be achieved by making everything require a capability: OPTIONS
     * (CORS preflight) and the self-service password path stay open, as before.
     */
    public function testParityDoesNotWidenOptionsOrSelfServicePaths(): void
    {
        self::assertNull(CapabilityResolver::resolve('/api/v1/users/me/password', 'HEAD'));
        self::assertNull(CapabilityResolver::resolve('/api/v1/entities/1', 'HEAD'));
    }
}
