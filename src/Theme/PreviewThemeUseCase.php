<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

use Nene2\Validation\ValidationException;

/**
 * Computes a no-browser preview of a theme manifest (#433): validates (reporting
 * rather than rejecting), determines which token values would actually render
 * (safe-filtered, mirroring the public `<style>` emit), and computes WCAG
 * contrast for key pairs in both modes. Pure — no persistence, no I/O.
 */
final class PreviewThemeUseCase
{
    private const TOKEN_KEY_PATTERN = '/^[a-z][a-z0-9-]*$/';

    /** [foreground token, background token, AA threshold, short label]. */
    private const PAIRS = [
        ['color-text-primary', 'color-surface', 4.5, 'text-primary/surface'],
        ['color-text-muted', 'color-surface', 4.5, 'text-muted/surface'],
        ['color-text-primary', 'color-surface-raised', 4.5, 'text-primary/surface-raised'],
        ['color-on-accent', 'color-accent', 4.5, 'on-accent/accent'],
        ['color-accent', 'color-surface', 3.0, 'accent/surface'],
        ['color-border-strong', 'color-surface', 3.0, 'border-strong/surface'],
    ];

    public function execute(PreviewThemeInput $input): PreviewThemeOutput
    {
        $errors = [];
        try {
            ThemeManifestValidator::validate($input->manifest);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $error) {
                $errors[] = ['field' => $error->field, 'message' => $error->message, 'code' => $error->code];
            }
        }

        $warnings = [];
        $dropped = [];
        $applied = [
            'light' => $this->applyMode($input->manifest, 'light', $dropped),
            'dark' => $this->applyMode($input->manifest, 'dark', $dropped),
        ];

        $contrast = [
            'light' => $this->contrastForMode($applied['light'], $warnings, 'light'),
            'dark' => $this->contrastForMode($applied['dark'], $warnings, 'dark'),
        ];

        return new PreviewThemeOutput(
            valid: $errors === [],
            errors: $errors,
            applied: $applied,
            dropped: $dropped,
            contrast: $contrast,
            warnings: $warnings,
        );
    }

    /**
     * Keep only the token values that would actually render (valid key + safe
     * value), recording the rest in $dropped.
     *
     * @param array<string, mixed> $manifest
     * @param list<array{mode: string, token: string, reason: string}> $dropped
     *
     * @return array<string, string>
     */
    private function applyMode(array $manifest, string $mode, array &$dropped): array
    {
        $tokens = $manifest['tokens'][$mode] ?? null;
        if (!is_array($tokens)) {
            return [];
        }

        $applied = [];
        foreach ($tokens as $key => $value) {
            if (!is_string($key) || preg_match(self::TOKEN_KEY_PATTERN, $key) !== 1) {
                $dropped[] = ['mode' => $mode, 'token' => is_string($key) ? $key : '?', 'reason' => 'invalid-key'];

                continue;
            }
            if (!is_string($value)) {
                $dropped[] = ['mode' => $mode, 'token' => $key, 'reason' => 'non-string'];

                continue;
            }
            if (!ThemeManifestValidator::isSafeCssValue($value)) {
                $dropped[] = ['mode' => $mode, 'token' => $key, 'reason' => 'unsafe-value'];

                continue;
            }
            $applied[$key] = $value;
        }

        return $applied;
    }

    /**
     * @param array<string, string> $tokens
     * @param list<string> $warnings
     *
     * @return list<array<string, mixed>>
     */
    private function contrastForMode(array $tokens, array &$warnings, string $mode): array
    {
        $result = [];
        foreach (self::PAIRS as [$fg, $bg, $threshold, $label]) {
            $fgValue = $tokens[$fg] ?? null;
            $bgValue = $tokens[$bg] ?? null;
            if ($fgValue === null || $bgValue === null) {
                $result[] = ['pair' => $label, 'computable' => false, 'reason' => 'missing-token'];

                continue;
            }
            $ratio = ColorContrast::ratio($fgValue, $bgValue);
            if ($ratio === null) {
                $result[] = ['pair' => $label, 'computable' => false, 'reason' => 'unparseable-color'];
                $warnings[] = "{$mode} {$label}: colour not parseable (e.g. color-mix/var), contrast not computed";

                continue;
            }
            $rounded = round($ratio, 2);
            $result[] = [
                'pair' => $label,
                'ratio' => $rounded,
                'aa' => $ratio >= $threshold,
                'aaa' => $ratio >= 7.0,
                'computable' => true,
            ];
        }

        return $result;
    }
}
