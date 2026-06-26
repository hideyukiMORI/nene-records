<?php

declare(strict_types=1);

namespace NeNeRecords\Auth;

enum Role: string
{
    case Superadmin = 'superadmin';
    case Admin = 'admin';
    case Editor = 'editor';

    public function hasCapability(Capability $capability): bool
    {
        return match ($this) {
            self::Superadmin => true,
            self::Admin => $capability !== Capability::ManageOrganizations,
            self::Editor => match ($capability) {
                Capability::ReadSettings,
                Capability::EditContent => true,
                Capability::ManageSchema,
                Capability::ManageSettings,
                Capability::ManageTags,
                Capability::ManageOrganizations,
                Capability::ManageAccount => false,
            },
        };
    }
}
