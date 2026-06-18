<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

final readonly class CreateThemeOutput
{
    public function __construct(
        public Theme $theme,
    ) {
    }
}
