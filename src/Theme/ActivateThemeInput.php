<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

final readonly class ActivateThemeInput
{
    public function __construct(
        public string $themeKey,
    ) {
    }
}
