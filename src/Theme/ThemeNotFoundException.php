<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

use DomainException;

final class ThemeNotFoundException extends DomainException
{
    public function __construct(string $themeKey)
    {
        parent::__construct("Theme '{$themeKey}' was not found.");
    }
}
