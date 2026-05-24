<?php

declare(strict_types=1);

namespace NeNeRecords\Auth;

enum Role: string
{
    case Admin = 'admin';
    case Editor = 'editor';

    public function hasCapability(Capability $capability): bool
    {
        return match ($this) {
            self::Admin => true,
            self::Editor => match ($capability) {
                Capability::ReadSettings,
                Capability::EditContent => true,
                Capability::ManageSchema,
                Capability::ManageSettings,
                Capability::ManageTags => false,
            },
        };
    }
}
