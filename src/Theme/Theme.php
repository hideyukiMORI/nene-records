<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

/**
 * A runtime (data-driven) public-site theme (#423). The `manifest` is the
 * validated public-theme.schema.json document (tokens / flags / knobs …);
 * `themeKey` / `name` / `version` are denormalised from it for listing and
 * uniqueness. See docs/theming/runtime-themes.md.
 */
final readonly class Theme
{
    /**
     * @param array<string, mixed> $manifest
     */
    public function __construct(
        public ?int $id,
        public string $themeKey,
        public string $name,
        public string $version,
        public array $manifest,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }
}
