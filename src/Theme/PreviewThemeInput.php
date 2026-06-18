<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

final readonly class PreviewThemeInput
{
    /** @param array<string, mixed> $manifest */
    public function __construct(
        public array $manifest,
    ) {
    }
}
