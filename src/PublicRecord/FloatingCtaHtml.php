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
    public static function render(FloatingCta $cta, string $typeSlug, string $path, ?string $nonce = null, string $lang = 'en'): string
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

        // A curated icon id (vetted first-party SVG) takes priority over the emoji. Only the
        // one selected icon's markup is emitted, and it is our own repo-shipped SVG — never
        // org-supplied — so verbatim output stays safe.
        if ($cta->iconId !== '') {
            $icon = '<span class="nene-fab__icon" aria-hidden="true">' . FloatingCtaIcons::svg($cta->iconId) . '</span>';
        } elseif ($cta->icon !== '') {
            $icon = '<span class="nene-fab__icon" aria-hidden="true">' . self::e($cta->icon) . '</span>';
        } else {
            $icon = '';
        }
        $sub = $cta->sub !== ''
            ? '<span class="nene-fab__sub">' . self::e($cta->sub) . '</span>'
            : '';

        // Dismiss (#982 P2 (a)): a "×" that remembers dismissal in localStorage via a small
        // nonce'd inline script. The renderer only supplies a nonce when it will also add it
        // to the CSP, so a missing nonce means "no dismiss UI" (graceful, still a working FAB).
        $dismiss = $cta->dismissible && $nonce !== null && $nonce !== '';

        $style = self::style($accent, $side, $cta->bottomOffset, $dismiss);

        $button = '<a class="nene-fab" href="' . $href . '"' . $target . $rel . '>'
            . $icon
            . '<span class="nene-fab__text">'
            . '<span class="nene-fab__label">' . $label . '</span>'
            . $sub
            . '</span>'
            . '</a>';

        $dismissBtn = '';
        $script = '';
        if ($dismiss) {
            $dismissBtn = '<button type="button" class="nene-fab__dismiss" aria-label="'
                . self::e(self::dismissLabel($lang)) . '">&#215;</button>';
            $script = self::script((string) $nonce);
        }

        // The wrapper is the fixed-positioned element so the dismiss button can sit at its
        // corner; the SPA never touches it (rendered outside #root before </body>).
        return $style . "\n"
            . '<div class="nene-fab-wrap" id="nene-fab-wrap">' . $button . $dismissBtn . '</div>'
            . $script;
    }

    private static function style(string $accent, string $side, int $bottomOffset, bool $dismiss): string
    {
        // Page-bottom clearance (#982 P2 (c)): reserve space so the fixed FAB never covers
        // footer content at scroll-end. Replaces the ad-hoc per-site `footer{padding-bottom}`
        // hack with a first-party, no-JS config knob. `$bottomOffset` is a validated int.
        $clearance = $bottomOffset > 0
            ? 'body{padding-bottom:calc(env(safe-area-inset-bottom,0px) + ' . $bottomOffset . 'px)}'
            : '';

        // The dismiss "×" sits at the wrapper's outer corner (opposite side stays clear).
        $dismissCss = $dismiss
            ? '.nene-fab__dismiss{position:absolute;top:-9px;' . $side . ':-9px;width:24px;height:24px;'
                . 'border:none;border-radius:999px;cursor:pointer;background:#14120F;color:#fff;'
                . 'font-size:15px;line-height:1;padding:0;display:flex;align-items:center;justify-content:center;'
                . 'box-shadow:0 2px 6px rgba(20,18,15,.3)}'
                . '.nene-fab__dismiss:hover{filter:brightness(1.25)}'
                . '.nene-fab__dismiss:focus-visible{outline:2px solid #14120F;outline-offset:2px}'
            : '';

        // Scoped to `.nene-fab*`; safe to inline (CSP style-src allows 'unsafe-inline').
        // The wrapper carries the fixed position; `.nene-fab` is the button visual. `$accent`
        // is a validated hex and `$side` a fixed keyword, so interpolation is injection-free.
        return '<style>'
            . $clearance
            . '.nene-fab-wrap{position:fixed;bottom:calc(env(safe-area-inset-bottom,0px) + 20px);'
            . $side . ':calc(env(safe-area-inset-' . $side . ',0px) + 20px);z-index:2147483000}'
            . '.nene-fab{display:inline-flex;align-items:center;gap:10px;'
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
            . $dismissCss
            . '@media(max-width:560px){.nene-fab-wrap{left:14px;right:14px}.nene-fab{display:flex;justify-content:center;padding:15px 18px}}'
            . '@media(prefers-reduced-motion:reduce){.nene-fab{transition:none}.nene-fab:hover{transform:none}}'
            . '</style>';
    }

    /**
     * The nonce'd dismiss script: a first-party constant (no interpolated data) that hides
     * the FAB immediately when already dismissed and remembers a "×" click in localStorage.
     * `$nonce` is hex from the renderer, so attribute interpolation carries no injection.
     */
    private static function script(string $nonce): string
    {
        $n = self::e($nonce);
        $js = "(function(){try{var k='nene-fab-dismissed',w=document.getElementById('nene-fab-wrap');"
            . "if(!w)return;if(localStorage.getItem(k)==='1'){w.style.display='none';return}"
            . "var b=w.querySelector('.nene-fab__dismiss');if(b){b.addEventListener('click',function(){"
            . "try{localStorage.setItem(k,'1')}catch(e){}w.style.display='none'})}}catch(e){}})();";

        return "<script nonce=\"{$n}\">{$js}</script>";
    }

    /** Localized accessible label for the dismiss button across the supported public locales. */
    private static function dismissLabel(string $lang): string
    {
        return match ($lang) {
            'ja' => '閉じる',
            'de' => 'Schließen',
            'fr' => 'Fermer',
            'pt-BR' => 'Fechar',
            'zh-Hans' => '关闭',
            default => 'Close',
        };
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
