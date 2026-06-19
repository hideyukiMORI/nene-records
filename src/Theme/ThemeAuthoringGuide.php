<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

/**
 * Builds the structured, in-band authoring guide ClaudeDesign reads over MCP
 * before registering a runtime theme (#440).
 *
 * The human-facing prose lives in docs/theming/claudedesign-mcp-guide.md, but an
 * agent connected through the MCP bridge cannot read the repository — so this
 * returns the machine-usable contract (required tokens, flag enums, value rules,
 * reserved ids) plus recipes and a minimal example. Everything contract-related
 * is derived from {@see ThemeManifestValidator::contract()} so it can never drift
 * from what the server actually enforces.
 */
final class ThemeAuthoringGuide
{
    /**
     * @return array<string, mixed>
     */
    public static function build(): array
    {
        $contract = ThemeManifestValidator::contract();

        return [
            'summary' => 'How to author and register a runtime (data-driven) public-site theme over MCP. '
                . 'A theme is a manifest: id/name/version + per-mode CSS tokens (light & dark) + optional structural flags. '
                . 'Token values are emitted verbatim into a scoped <style> on the public site, so they are sanitised: '
                . 'only safe CSS passes. Build the manifest, then call createTheme (or updateTheme to replace one).',
            'authentication' => 'All theme tools require an authenticated admin token; the MCP bridge already attaches it. '
                . 'Data is organization-scoped (JWT org_id).',
            'contract' => $contract,
            'recipes' => self::recipes(),
            'exampleManifest' => self::exampleManifest($contract['requiredTokens']),
            'commonMistakes' => [
                'Omitting a required token in light OR dark — both modes must carry the full set.',
                'Using url(), @import, or a semicolon/braces in a token value — rejected as unsafe CSS (422).',
                'Reusing a built-in theme id (see contract.reservedIds) — rejected as reserved (422).',
                'Pointing an asset at an external URL or data: URI — only a media id or bundle-relative path is allowed.',
                'A flag value outside its enum (see contract.flags) — rejected as invalid.',
            ],
            'relatedTools' => [
                'listThemes' => 'List existing runtime themes for the org.',
                'getTheme' => 'Fetch one runtime theme by key.',
                'createTheme' => 'Register a new runtime theme from a manifest.',
                'updateTheme' => 'Replace an existing theme manifest (id must equal the key).',
                'deleteTheme' => 'Delete a runtime theme by key.',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function recipes(): array
    {
        return [
            [
                'title' => 'Create a new theme',
                'steps' => [
                    'Pick an id matching the id pattern that is not in contract.reservedIds.',
                    'Fill tokens.light and tokens.dark with every token in contract.requiredTokens (safe CSS values).',
                    'Optionally set structural flags from contract.flags.',
                    'Call createTheme with the manifest. A 422 lists exactly which fields failed.',
                ],
            ],
            [
                'title' => 'Adjust an existing theme\'s colors',
                'steps' => [
                    'getTheme to fetch the current manifest.',
                    'Edit the token values you want to change (keep all required tokens present).',
                    'Call updateTheme with the full manifest; id must equal the key.',
                ],
            ],
            [
                'title' => 'Fix unreadable button/accent text',
                'steps' => [
                    'Ensure color-on-accent contrasts with color-accent (and the same in dark mode).',
                    'Re-submit via updateTheme.',
                ],
            ],
        ];
    }

    /**
     * A minimal manifest that passes {@see ThemeManifestValidator::validate()}.
     * Token values are illustrative placeholders — replace them with the real
     * palette. Generated from the required-token list so it always stays complete.
     *
     * @param list<string> $requiredTokens
     *
     * @return array<string, mixed>
     */
    private static function exampleManifest(array $requiredTokens): array
    {
        return [
            'id' => 'example-theme',
            'name' => 'Example Theme',
            'version' => '1.0.0',
            'supportsModes' => ['light', 'dark'],
            'tokens' => [
                'light' => self::sampleTokens($requiredTokens, 'light'),
                'dark' => self::sampleTokens($requiredTokens, 'dark'),
            ],
            'flags' => [
                'feedLayout' => 'grid',
                'cardStyle' => 'bordered',
            ],
        ];
    }

    /**
     * @param list<string> $requiredTokens
     *
     * @return array<string, string>
     */
    private static function sampleTokens(array $requiredTokens, string $mode): array
    {
        $tokens = [];
        foreach ($requiredTokens as $key) {
            $tokens[$key] = match (true) {
                $key === 'color-scheme' => $mode,
                str_starts_with($key, 'shadow-') => '0 1px 2px rgba(0, 0, 0, 0.08)',
                default => $mode === 'light' ? '#1a1a1a' : '#f5f5f5',
            };
        }

        return $tokens;
    }
}
