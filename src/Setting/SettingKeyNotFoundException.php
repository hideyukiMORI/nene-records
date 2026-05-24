<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

use RuntimeException;

final class SettingKeyNotFoundException extends RuntimeException
{
    public function __construct(string $settingKey)
    {
        parent::__construct("Setting with key {$settingKey} was not found.");
    }
}
