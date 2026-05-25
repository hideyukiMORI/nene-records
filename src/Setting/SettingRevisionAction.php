<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

enum SettingRevisionAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Restored = 'restored';
}
