<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Auth;

use NeNeRecords\Auth\Capability;
use NeNeRecords\Auth\Role;
use PHPUnit\Framework\TestCase;

final class RoleTest extends TestCase
{
    public function testAdminHasAllCapabilities(): void
    {
        foreach (Capability::cases() as $capability) {
            self::assertTrue(Role::Admin->hasCapability($capability));
        }
    }

    public function testEditorCanEditContentAndReadSettings(): void
    {
        self::assertTrue(Role::Editor->hasCapability(Capability::EditContent));
        self::assertTrue(Role::Editor->hasCapability(Capability::ReadSettings));
    }

    public function testEditorCannotManageSchemaOrSettings(): void
    {
        self::assertFalse(Role::Editor->hasCapability(Capability::ManageSchema));
        self::assertFalse(Role::Editor->hasCapability(Capability::ManageSettings));
        self::assertFalse(Role::Editor->hasCapability(Capability::ManageTags));
    }
}
