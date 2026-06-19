<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

/**
 * Builds the structured, in-band authoring guide ClaudeDesign reads over MCP
 * before registering a runtime theme (#440, enriched in #442).
 *
 * The human-facing prose lives in docs/theming/claudedesign-mcp-guide.md, but an
 * agent connected through the MCP bridge cannot read the repository — so this
 * returns the machine-usable contract (required tokens, flag enums, value rules,
 * reserved ids) AND the semantic knowledge needed to author a *good* theme, not
 * just a valid one: what each token means, which tokens must contrast, what each
 * flag controls, a realistic example, and the fonts/assets shapes.
 *
 * Contract data is derived from {@see ThemeManifestValidator::contract()}; the
 * per-token/flag docs are static knowledge, kept aligned by a test that asserts
 * their keys match the contract (so adding a token forces a doc update).
 */
final class ThemeAuthoringGuide
{
    /**
     * Per required-token semantics + a realistic value for each mode.
     * [purpose, pairsWith (token to contrast against, or ''), light, dark].
     * Keys MUST stay equal to ThemeManifestValidator::contract().requiredTokens.
     *
     * @var array<string, array{string, string, string, string}>
     */
    private const TOKEN_SPECS = [
        'color-surface' => ['Base page background.', 'color-text-primary', '#ffffff', '#0f1115'],
        'color-surface-raised' => ['Background for raised cards/panels above the page.', 'color-text-primary', '#f7f8fa', '#171a21'],
        'color-surface-overlay' => ['Background for overlays (modals, menus, popovers).', 'color-text-primary', '#ffffff', '#1c2027'],
        'color-surface-sunken' => ['Background for inset/well areas below the page.', 'color-text-primary', '#eef0f3', '#0b0d11'],
        'color-text-primary' => ['Primary body and heading text.', 'color-surface', '#1a1d23', '#f3f4f6'],
        'color-text-muted' => ['Secondary text (captions, meta, placeholders).', 'color-surface', '#5b6472', '#9ca3af'],
        'color-text-inverse' => ['Text shown on strong/dark fills.', '', '#ffffff', '#0f1115'],
        'color-border' => ['Default hairline borders and dividers.', '', '#e4e7ec', '#2a2f3a'],
        'color-border-strong' => ['Emphasised borders (active/focused inputs).', '', '#cfd4dc', '#3a414f'],
        'color-focus-ring' => ['Keyboard focus ring; must stay visible on surfaces.', 'color-surface', '#2563eb', '#60a5fa'],
        'color-accent' => ['Primary brand/action color (buttons, links).', 'color-on-accent', '#2563eb', '#3b82f6'],
        'color-accent-hover' => ['Accent color on hover/active.', 'color-on-accent', '#1d4ed8', '#60a5fa'],
        'color-accent-weak' => ['Tinted accent background (chips, subtle highlights).', 'color-text-primary', '#dbeafe', '#1e293b'],
        'color-on-accent' => ['Text/icon color on top of color-accent; must contrast it.', 'color-accent', '#ffffff', '#ffffff'],
        'color-brand-violet' => ['Secondary brand accent (violet).', '', '#7c3aed', '#a78bfa'],
        'color-danger' => ['Destructive/error color.', 'color-text-inverse', '#dc2626', '#ef4444'],
        'color-danger-hover' => ['Danger color on hover/active.', 'color-text-inverse', '#b91c1c', '#f87171'],
        'color-ok' => ['Success/positive state color.', '', '#16a34a', '#22c55e'],
        'color-warn' => ['Warning state color.', '', '#d97706', '#f59e0b'],
        'color-info' => ['Informational state color.', '', '#0ea5e9', '#38bdf8'],
        'shadow-sm' => ['Small elevation shadow (subtle lift).', '', '0 1px 2px rgba(0, 0, 0, 0.06)', '0 1px 2px rgba(0, 0, 0, 0.4)'],
        'shadow-md' => ['Medium elevation shadow (cards, dropdowns).', '', '0 4px 12px rgba(0, 0, 0, 0.10)', '0 4px 12px rgba(0, 0, 0, 0.5)'],
        'shadow-lg' => ['Large elevation shadow (modals, popovers).', '', '0 12px 32px rgba(0, 0, 0, 0.16)', '0 12px 32px rgba(0, 0, 0, 0.6)'],
        'color-scheme' => ["CSS color-scheme keyword for the mode: 'light' in light, 'dark' in dark.", '', 'light', 'dark'],
    ];

    /** What each structural flag controls. Keys MUST equal contract().flags keys. */
    private const FLAG_DOCS = [
        'feedLayout' => 'How the post feed is arranged (grid / list / magazine).',
        'feedColumns' => 'Column count of the feed grid; auto = responsive.',
        'cardStyle' => 'Visual treatment of cards (flat / bordered / shadowed / framed).',
        'media' => 'Image/thumbnail rendering treatment (plain / duotone / grayscale / framed).',
        'hero' => 'Top hero/banner layout (standard / fullbleed / minimal).',
        'sectionRule' => 'Divider style between sections (none / hairline / heavy).',
        'eyebrow' => 'Style of the small kicker label above titles (plain / caps / barred).',
        'headerSearch' => 'Show or hide the header search control.',
        'headerTheme' => 'Show or hide the header light/dark toggle.',
        'headerTagline' => 'Show or hide the site tagline in the header.',
        'headerLayout' => 'Header skeleton preset (nav-right / classic / centered / minimal).',
        'headerNavAlign' => 'Alignment of the nav within the header (start / center / end).',
        'headerDensity' => 'Vertical density/height of the header (compact / regular / tall).',
        'headerWidth' => 'Header content width (boxed / full-bleed).',
        'headerSticky' => 'Whether the header sticks to the top on scroll (sticky / none).',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function build(): array
    {
        $contract = ThemeManifestValidator::contract();

        return [
            'summary' => 'How to author and register a runtime (data-driven) public-site theme over MCP. '
                . 'A theme is a manifest: id/name/version + per-mode CSS tokens (light & dark) + optional structural flags, '
                . 'fonts and assets. Token values are emitted verbatim into a scoped <style> on the public site, so they are '
                . 'sanitised: only safe CSS passes. Read tokenDocs for what each token means and which must contrast, copy '
                . 'exampleManifest as a starting palette, then call createTheme (or updateTheme to replace one).',
            'authentication' => 'All theme tools require an authenticated admin token; the MCP bridge already attaches it. '
                . 'Data is organization-scoped (JWT org_id).',
            'contract' => $contract,
            'tokenDocs' => self::tokenDocs(),
            'flagDocs' => self::FLAG_DOCS,
            'recipes' => self::recipes(),
            'exampleManifest' => self::exampleManifest(),
            'optionalFields' => self::optionalFields(),
            'commonMistakes' => [
                'Inverting modes: light mode must use light surfaces + dark text; dark mode the opposite.',
                'Low contrast: color-text-primary must read on color-surface, and color-on-accent on color-accent (see tokenDocs pairsWith).',
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
     * @return array<string, array{purpose: string, pairsWith: string}>
     */
    private static function tokenDocs(): array
    {
        $docs = [];
        foreach (self::TOKEN_SPECS as $token => [$purpose, $pairsWith]) {
            $docs[$token] = ['purpose' => $purpose, 'pairsWith' => $pairsWith];
        }

        return $docs;
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
                    'Start from exampleManifest; restyle each token using tokenDocs for meaning and contrast pairs.',
                    'Keep every token in contract.requiredTokens present in BOTH light and dark.',
                    'Optionally set structural flags from contract.flags (see flagDocs).',
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
     * A realistic manifest that passes {@see ThemeManifestValidator::validate()}:
     * light = light surfaces + dark text, dark = the inverse, accent paired with
     * a contrasting on-accent. Generated from TOKEN_SPECS so it always covers the
     * full required-token set.
     *
     * @return array<string, mixed>
     */
    private static function exampleManifest(): array
    {
        $light = [];
        $dark = [];
        foreach (self::TOKEN_SPECS as $token => [, , $lightValue, $darkValue]) {
            $light[$token] = $lightValue;
            $dark[$token] = $darkValue;
        }

        return [
            'id' => 'example-theme',
            'name' => 'Example Theme',
            'version' => '1.0.0',
            'supportsModes' => ['light', 'dark'],
            'tokens' => ['light' => $light, 'dark' => $dark],
            'flags' => [
                'feedLayout' => 'grid',
                'cardStyle' => 'bordered',
                'headerLayout' => 'nav-right',
            ],
        ];
    }

    /**
     * Shapes for the optional fonts/assets blocks (omitted from exampleManifest
     * to keep it minimal). Runtime themes may only use bundled fonts.
     *
     * @return array<string, mixed>
     */
    private static function optionalFields(): array
    {
        return [
            'fonts' => [
                'shape' => '{ family, role, source, weights? }',
                'roles' => ['display', 'body', 'chrome', 'mono'],
                'sources' => ['fontsource', 'system'],
                'note' => "Runtime themes may only use 'fontsource' or 'system' (self-hosted files need a deploy). family is a font name, weights is an optional list of 100–900.",
                'example' => [
                    ['family' => 'Inter', 'role' => 'body', 'source' => 'fontsource', 'weights' => [400, 600, 700]],
                    ['family' => 'Space Grotesk', 'role' => 'display', 'source' => 'fontsource'],
                ],
            ],
            'assets' => [
                'slots' => ['preview', 'hero', 'background'],
                'value' => 'A media id (positive integer) or a safe bundle-relative path. Never an external URL or data: URI. A per-mode object {light, dark} is allowed.',
                'note' => 'assets.preview drives the theme picker thumbnail.',
                'example' => ['preview' => 123],
            ],
        ];
    }
}
