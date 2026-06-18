<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

final readonly class DeleteThemeInput
{
    public function __construct(
        public string $themeKey,
    ) {
    }
}
