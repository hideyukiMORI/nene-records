<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;

/**
 * Validates a runtime theme manifest before it is stored / served (#423).
 *
 * Mirrors docs/theming/public-theme.schema.json AND adds the runtime safety the
 * JSON schema cannot express: because token values are emitted verbatim as
 * `--key: <value>;` inside a scoped `<style>` on the public site, an untrusted
 * value could break out of the declaration / stylesheet. So every token value
 * is constrained to safe CSS (no `;{}<>`, no `url(`/`@import`/`expression(`/
 * `javascript:`), and token keys are allowlisted by pattern. This is the crux
 * of letting ClaudeDesign register themes over MCP. See runtime-themes.md §6.
 */
final class ThemeManifestValidator
{
    private const ID_PATTERN = '/^[a-z][a-z0-9-]{1,40}$/';
    private const VERSION_PATTERN = '/^[0-9]+\.[0-9]+\.[0-9]+$/';
    private const TOKEN_KEY_PATTERN = '/^[a-z][a-z0-9-]*$/';
    /** Bundle-relative asset path; rejects external URLs and data: (schema $defs.assetRef). */
    private const ASSET_REF_PATTERN = '#^(?!https?:)(?!//)(?!data:)[A-Za-z0-9._/-]+\.(png|jpg|jpeg|webp|avif|svg)$#';

    /** Required contract tokens per mode (mirrors schema $defs.tokenSet.required). */
    private const REQUIRED_TOKENS = [
        'color-surface', 'color-surface-raised', 'color-surface-overlay', 'color-surface-sunken',
        'color-text-primary', 'color-text-muted', 'color-text-inverse',
        'color-border', 'color-border-strong', 'color-focus-ring',
        'color-accent', 'color-accent-hover', 'color-accent-weak', 'color-on-accent',
        'color-brand-violet', 'color-danger', 'color-danger-hover',
        'color-ok', 'color-warn', 'color-info',
        'shadow-sm', 'shadow-md', 'shadow-lg', 'color-scheme',
    ];

    /** Structural style-flag enums (mirrors theme-customization.ts FLAG_DEFS). */
    private const FLAG_ENUMS = [
        'feedLayout' => ['grid', 'list', 'magazine'],
        'feedColumns' => ['auto', '1', '2', '3', '4'],
        'cardStyle' => ['flat', 'bordered', 'shadowed', 'framed'],
        'media' => ['plain', 'duotone', 'grayscale', 'framed'],
        'hero' => ['standard', 'fullbleed', 'minimal', 'split', 'gradient'],
        'sectionRule' => ['none', 'hairline', 'heavy'],
        'eyebrow' => ['plain', 'caps', 'barred'],
        'headerSearch' => ['show', 'hide'],
        'headerTheme' => ['show', 'hide'],
        'headerTagline' => ['show', 'hide'],
        'headerLayout' => ['nav-right', 'classic', 'centered', 'minimal'],
        'headerNavAlign' => ['start', 'center', 'end'],
        'headerDensity' => ['compact', 'regular', 'tall'],
        'headerWidth' => ['boxed', 'full'],
        'headerSticky' => ['sticky', 'none'],
        'motionReveal' => ['off', 'subtle', 'standard'],
        'motionHeader' => ['static', 'shrink'],
    ];

    /**
     * Built-in (static) theme ids — runtime themes must not shadow them.
     * Must cover every `[data-theme='id']` defined under
     * frontend/src/shared/ui/theme/themes/*.css; ThemeReservedKeysSyncTest
     * fails if a built-in is added without updating this list.
     */
    private const RESERVED_KEYS = [
        'academia', 'aurora', 'botanical', 'brutalist', 'coastal', 'comic',
        'consumer', 'deco', 'funk', 'gothic', 'japandi', 'kraft', 'luxe',
        'memphis', 'nebula', 'newsprint', 'noir', 'pastel', 'reading', 'riso',
        'stadium', 'sumi', 'swiss', 'synthwave', 'terminal', 'western', 'y2k',
    ];

    private const MAX_TOKEN_VALUE_LEN = 200;

    /**
     * Substrings that make a token value unsafe (external / script-bearing).
     * Extracted so the authoring guide (#440) can expose them verbatim.
     *
     * @var list<string>
     */
    private const UNSAFE_VALUE_SUBSTRINGS = ['url(', '@import', 'expression(', 'javascript:', '/*', '*/'];

    /** Break-out characters rejected in any token value. */
    private const UNSAFE_VALUE_CHARS = ';{}<>\\`';

    /**
     * Contract that ClaudeDesign must satisfy to register a theme over MCP.
     * Derived from this validator so the authoring guide can never drift from
     * what is actually enforced (#440).
     *
     * @return array<string, mixed>
     */
    public static function contract(): array
    {
        return [
            'idPattern' => self::ID_PATTERN,
            'versionPattern' => self::VERSION_PATTERN,
            'tokenKeyPattern' => self::TOKEN_KEY_PATTERN,
            'assetRefPattern' => self::ASSET_REF_PATTERN,
            'requiredTokens' => self::REQUIRED_TOKENS,
            'flags' => self::FLAG_ENUMS,
            'reservedIds' => self::RESERVED_KEYS,
            'tokenValueRules' => [
                'maxLength' => self::MAX_TOKEN_VALUE_LEN,
                'forbiddenChars' => self::UNSAFE_VALUE_CHARS,
                'forbiddenSubstrings' => self::UNSAFE_VALUE_SUBSTRINGS,
            ],
            'fontSources' => ['fontsource', 'system'],
        ];
    }

    /** Whether $id is a built-in (static) theme — activatable, but reserved against runtime registration. */
    public static function isBuiltin(string $id): bool
    {
        return in_array($id, self::RESERVED_KEYS, true);
    }

    /**
     * @param array<string, mixed> $manifest
     *
     * @throws ValidationException when the manifest is malformed or unsafe
     */
    public static function validate(array $manifest): void
    {
        $errors = [];

        $id = $manifest['id'] ?? null;
        if (!is_string($id) || preg_match(self::ID_PATTERN, $id) !== 1) {
            $errors[] = new ValidationError('id', 'Theme id must match ^[a-z][a-z0-9-]{1,40}$.', 'invalid');
        } elseif (in_array($id, self::RESERVED_KEYS, true)) {
            $errors[] = new ValidationError('id', "Theme id '{$id}' is reserved by a built-in theme.", 'reserved');
        }

        $name = $manifest['name'] ?? null;
        if (!is_string($name) || trim($name) === '' || mb_strlen($name) > 80) {
            $errors[] = new ValidationError('name', 'Name is required (1–80 chars).', 'invalid');
        }

        $version = $manifest['version'] ?? null;
        if (!is_string($version) || preg_match(self::VERSION_PATTERN, $version) !== 1) {
            $errors[] = new ValidationError('version', 'Version must be semver (e.g. 1.0.0).', 'invalid');
        }

        self::validateModes($manifest['supportsModes'] ?? null, $errors);
        self::validateTokens($manifest['tokens'] ?? null, $errors);
        self::validateFlags($manifest['flags'] ?? null, $errors);
        self::validateFonts($manifest['fonts'] ?? null, $errors);
        self::validateAssets($manifest['assets'] ?? null, $errors);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    /**
     * @param list<ValidationError> $errors
     */
    private static function validateModes(mixed $modes, array &$errors): void
    {
        if (!is_array($modes) || !in_array('light', $modes, true) || !in_array('dark', $modes, true)) {
            $errors[] = new ValidationError('supportsModes', 'supportsModes must include both light and dark.', 'invalid');
        }
    }

    /**
     * @param list<ValidationError> $errors
     */
    private static function validateTokens(mixed $tokens, array &$errors): void
    {
        if (!is_array($tokens) || !isset($tokens['light']) || !isset($tokens['dark'])) {
            $errors[] = new ValidationError('tokens', 'tokens.light and tokens.dark are required.', 'required');

            return;
        }

        foreach (['light', 'dark'] as $mode) {
            $set = $tokens[$mode];
            if (!is_array($set)) {
                $errors[] = new ValidationError("tokens.{$mode}", 'Token set must be an object.', 'invalid');

                continue;
            }

            foreach (self::REQUIRED_TOKENS as $required) {
                if (!array_key_exists($required, $set)) {
                    $errors[] = new ValidationError("tokens.{$mode}.{$required}", 'Required token is missing.', 'required');
                }
            }

            foreach ($set as $key => $value) {
                $field = "tokens.{$mode}." . (is_string($key) ? $key : '?');
                if (!is_string($key) || preg_match(self::TOKEN_KEY_PATTERN, $key) !== 1) {
                    $errors[] = new ValidationError($field, 'Token name must match ^[a-z][a-z0-9-]*$.', 'invalid');

                    continue;
                }
                if (!is_string($value) || !self::isSafeCssValue($value)) {
                    $errors[] = new ValidationError($field, 'Token value is empty or contains unsafe CSS.', 'unsafe');
                }
            }
        }
    }

    /**
     * @param list<ValidationError> $errors
     */
    private static function validateFlags(mixed $flags, array &$errors): void
    {
        if ($flags === null) {
            return;
        }
        if (!is_array($flags)) {
            $errors[] = new ValidationError('flags', 'flags must be an object.', 'invalid');

            return;
        }
        foreach ($flags as $key => $value) {
            if (!is_string($key) || !array_key_exists($key, self::FLAG_ENUMS)) {
                $errors[] = new ValidationError('flags.' . (is_string($key) ? $key : '?'), 'Unknown flag.', 'invalid');

                continue;
            }
            if (!is_string($value) || !in_array($value, self::FLAG_ENUMS[$key], true)) {
                $errors[] = new ValidationError("flags.{$key}", 'Value is not in the allowed set.', 'invalid');
            }
        }
    }

    /**
     * @param list<ValidationError> $errors
     */
    private static function validateFonts(mixed $fonts, array &$errors): void
    {
        if ($fonts === null) {
            return;
        }
        if (!is_array($fonts)) {
            $errors[] = new ValidationError('fonts', 'fonts must be an array.', 'invalid');

            return;
        }
        foreach ($fonts as $i => $font) {
            // Runtime themes may only use bundled fonts; self-hosted files need a
            // deploy, so they cannot be registered over MCP (runtime-themes.md §6).
            $source = is_array($font) ? ($font['source'] ?? null) : null;
            if (!in_array($source, ['fontsource', 'system'], true)) {
                $errors[] = new ValidationError("fonts.{$i}.source", 'Runtime fonts must be fontsource or system.', 'invalid');
            }
        }
    }

    /**
     * Assets (preview / hero / logo …) may only be a media id (positive int) or
     * a safe bundle-relative path — never an external URL or `data:` URI, which
     * the picker would otherwise fetch. Validated recursively so per-mode
     * objects ({light,dark}) and decoration arrays are covered (#426).
     *
     * @param list<ValidationError> $errors
     */
    private static function validateAssets(mixed $assets, array &$errors): void
    {
        if ($assets === null) {
            return;
        }
        if (!is_array($assets)) {
            $errors[] = new ValidationError('assets', 'assets must be an object.', 'invalid');

            return;
        }
        self::assertAssetNode('assets', $assets, $errors);
    }

    /**
     * @param list<ValidationError> $errors
     */
    private static function assertAssetNode(string $field, mixed $node, array &$errors): void
    {
        if (is_int($node)) {
            if ($node <= 0) {
                $errors[] = new ValidationError($field, 'Media id must be a positive integer.', 'invalid');
            }

            return;
        }
        if (is_string($node)) {
            if (preg_match(self::ASSET_REF_PATTERN, $node) !== 1) {
                $errors[] = new ValidationError($field, 'Asset must be a media id or safe bundle-relative path (no external URL or data:).', 'unsafe');
            }

            return;
        }
        if (is_array($node)) {
            foreach ($node as $key => $value) {
                self::assertAssetNode($field . '.' . (is_string($key) ? $key : (string) $key), $value, $errors);
            }

            return;
        }
        $errors[] = new ValidationError($field, 'Unsupported asset value.', 'invalid');
    }

    /**
     * A token value is safe when it carries no characters that could break out
     * of `--key: <value>;` or the enclosing `<style>`, and no external/script
     * constructs. Allows oklch()/hex/clamp()/color-mix()/lengths/keywords.
     */
    public static function isSafeCssValue(string $value): bool
    {
        $trimmed = trim($value);
        if ($trimmed === '' || mb_strlen($trimmed) > self::MAX_TOKEN_VALUE_LEN) {
            return false;
        }
        // Declaration / stylesheet break-out characters.
        if (preg_match('/[' . preg_quote(self::UNSAFE_VALUE_CHARS, '/') . ']/', $trimmed) === 1) {
            return false;
        }
        // External / script-bearing constructs (case-insensitive).
        $lower = strtolower($trimmed);
        foreach (self::UNSAFE_VALUE_SUBSTRINGS as $needle) {
            if (str_contains($lower, $needle)) {
                return false;
            }
        }

        return true;
    }
}
