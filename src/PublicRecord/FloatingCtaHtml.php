<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

/**
 * Renders the floating CTA ({@see FloatingCta}) as a server-generated chrome block
 * for the public shell (EPIC #982, P1): a scoped inline `<style>` plus a single
 * `<a>` button, or '' when disabled / not matching the current page.
 *
 * This block is emitted verbatim into the shell right before `</body>` (outside
 * `#root`, so the SPA mount does not wipe it). It is NOT run through
 * {@see PublicHtmlSanitizer} — that is deliberate and the whole point of the feature:
 * unlike a hand-embedded button in a record body (where class/`<svg>`/`<style>` are
 * stripped), a first-party chrome element keeps real CSS, `:hover` and `@media`.
 *
 * Safety: every admin-supplied value is escaped here (text via htmlspecialchars, the
 * href via the same, the accent constrained to a `#RRGGBB` shape upstream, the URL
 * scheme allowlisted upstream). CSP for the public surface is `style-src 'self'
 * 'unsafe-inline'`, so the inline `<style>` needs no nonce; the block is CSS-only
 * (no `<script>`), matching P1's "no arbitrary JS" rule.
 */
final class FloatingCtaHtml
{
    public static function render(FloatingCta $cta, string $typeSlug, string $path): string
    {
        if (!$cta->shouldRender($typeSlug, $path)) {
            return '';
        }

        $accent = $cta->accent; // already validated to /^#[0-9A-Fa-f]{6}$/
        $side = $cta->position === 'bl' ? 'left' : 'right';

        $href = self::e($cta->url);
        $label = self::e($cta->label);
        $rel = $cta->newTab ? ' rel="noopener noreferrer"' : '';
        $target = $cta->newTab ? ' target="_blank"' : '';

        $icon = $cta->icon !== ''
            ? '<span class="nene-fab__icon" aria-hidden="true">' . self::e($cta->icon) . '</span>'
            : '';
        $sub = $cta->sub !== ''
            ? '<span class="nene-fab__sub">' . self::e($cta->sub) . '</span>'
            : '';

        $style = self::style($accent, $side);

        return $style . "\n"
            . '<a class="nene-fab" href="' . $href . '"' . $target . $rel . '>'
            . $icon
            . '<span class="nene-fab__text">'
            . '<span class="nene-fab__label">' . $label . '</span>'
            . $sub
            . '</span>'
            . '</a>';
    }

    private static function style(string $accent, string $side): string
    {
        // Scoped to `.nene-fab`; safe to inline (CSP style-src allows 'unsafe-inline').
        // `$accent` is a validated hex and `$side` is a fixed keyword, so interpolation
        // is injection-free.
        return '<style>'
            . '.nene-fab{position:fixed;bottom:calc(env(safe-area-inset-bottom,0px) + 20px);'
            . $side . ':calc(env(safe-area-inset-' . $side . ',0px) + 20px);z-index:2147483000;'
            . 'display:inline-flex;align-items:center;gap:10px;'
            . 'background:var(--nene-fab-bg,' . $accent . ');color:var(--nene-fab-fg,#fff);'
            . 'text-decoration:none;font-family:inherit;font-weight:700;font-size:.92rem;line-height:1.15;'
            . 'padding:13px 20px;border-radius:999px;'
            . 'box-shadow:0 12px 28px rgba(20,18,15,.24),0 3px 8px rgba(20,18,15,.15);'
            . 'transition:transform .16s ease,box-shadow .16s ease,filter .16s ease}'
            . '.nene-fab:hover{transform:translateY(-2px);filter:brightness(1.06);'
            . 'box-shadow:0 16px 34px rgba(20,18,15,.28),0 4px 10px rgba(20,18,15,.18)}'
            . '.nene-fab:focus-visible{outline:3px solid #14120F;outline-offset:3px}'
            . '.nene-fab__icon{font-size:1.2em;line-height:1}'
            . '.nene-fab__text{display:inline-flex;flex-direction:column}'
            . '.nene-fab__sub{font-size:.62rem;font-weight:500;letter-spacing:.04em;opacity:.85;margin-top:2px}'
            . '@media(max-width:560px){.nene-fab{left:14px;right:14px;justify-content:center;padding:15px 18px}}'
            . '@media(prefers-reduced-motion:reduce){.nene-fab{transition:none}.nene-fab:hover{transform:none}}'
            . '</style>';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
