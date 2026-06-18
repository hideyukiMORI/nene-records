<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

final readonly class UpdateThemeInput
{
    /** @param array<string, mixed> $manifest */
    public function __construct(
        public string $themeKey,
        public array $manifest,
    ) {
    }
}
