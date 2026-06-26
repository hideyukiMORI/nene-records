<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

final readonly class ActivateThemeOutput
{
    public function __construct(
        public string $activeTheme,
    ) {
    }
}
