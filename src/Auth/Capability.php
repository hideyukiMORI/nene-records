<?php

declare(strict_types=1);

namespace NeNeRecords\Auth;

enum Capability
{
    case ManageSchema;
    case ManageSettings;
    case ReadSettings;
    case ManageTags;
    case EditContent;
}
