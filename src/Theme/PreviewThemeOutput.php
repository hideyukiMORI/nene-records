<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

/**
 * Computed (no-browser) preview of a theme manifest (#433): structural validity,
 * the tokens that would actually render (safe-filtered), the ones dropped, and
 * WCAG contrast for key pairs per mode.
 */
final readonly class PreviewThemeOutput
{
    /**
     * @param list<array{field: string, message: string, code: string}> $errors
     * @param array{light: array<string, string>, dark: array<string, string>} $applied
     * @param list<array{mode: string, token: string, reason: string}> $dropped
     * @param array{light: list<array<string, mixed>>, dark: list<array<string, mixed>>} $contrast
     * @param list<string> $warnings
     */
    public function __construct(
        public bool $valid,
        public array $errors,
        public array $applied,
        public array $dropped,
        public array $contrast,
        public array $warnings,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'errors' => $this->errors,
            'applied' => $this->applied,
            'dropped' => $this->dropped,
            'contrast' => $this->contrast,
            'warnings' => $this->warnings,
        ];
    }
}
