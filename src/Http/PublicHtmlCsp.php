<?php

declare(strict_types=1);

namespace NeNeRecords\Http;

/**
 * Content-Security-Policy for public SPA-bearing HTML responses (server-rendered
 * record pages and the SPA shell fallback).
 *
 * The framework default `default-src 'self'` is too strict for the single-origin
 * SPA: it injects inline `<style>` (runtime theme tokens) and loads `data:` fonts,
 * which `default-src 'self'` blocks. This relaxes only `style-src` / `font-src` /
 * `img-src` — scripts stay `'self'` (no `script-src 'unsafe-inline'`), and API
 * responses keep the strict framework default.
 *
 * When the org has configured GA4 / GTM ({@see WebAnalyticsConfig}), {@see build()}
 * widens the policy *only as much as analytics needs*: a per-response `nonce` plus
 * `https://www.googletagmanager.com` on `script-src`, and the Google Analytics
 * endpoints on `connect-src` / `img-src`. With no analytics configured the policy
 * is byte-for-byte {@see POLICY} (scripts stay `'self'`, no nonce, no third party).
 *
 * When the org has configured a trusted-embed allowlist ({@see EmbedAllowlist}, #802),
 * the listed origins are added to `script-src` / `connect-src` / `frame-src` — and
 * only those directives — so a first-party embed hosted on a self-owned subdomain can
 * load, call its API, and (if it opens one) frame itself. An **empty** allowlist leaves
 * the policy byte-for-byte identical to the analytics-only result (the embed path is a
 * pure add-on): the existing return values are reached by the unchanged code below.
 */
final class PublicHtmlCsp
{
    public const POLICY = "default-src 'self'; "
        . "style-src 'self' 'unsafe-inline'; "
        . "font-src 'self' data:; "
        . "img-src 'self' data:";

    private function __construct()
    {
    }

    /**
     * Policy for an analytics-aware public HTML response. Returns the strict
     * {@see POLICY} unchanged when analytics is disabled; otherwise allows the
     * nonce'd inline tag and the Google Analytics / Tag Manager hosts. A non-empty
     * trusted-embed allowlist adds those origins to script/connect/frame-src.
     */
    public static function build(
        WebAnalyticsConfig $analytics,
        ?string $scriptNonce = null,
        ?EmbedAllowlist $embeds = null,
    ): string {
        $embedOrigins = $embeds?->origins() ?? [];

        $hasNonce = $scriptNonce !== null && $scriptNonce !== '';

        // ── Fast path: no embeds ──
        if ($embedOrigins === []) {
            // Nothing to widen: strict POLICY unchanged (byte-for-byte as pre-#802).
            if (!$analytics->isEnabled() && !$hasNonce) {
                return self::POLICY;
            }

            $scriptSrc = "'self'";
            if ($hasNonce) {
                $scriptSrc .= " 'nonce-{$scriptNonce}'";
            }
            if ($analytics->isEnabled()) {
                $scriptSrc .= ' https://www.googletagmanager.com';
            }

            // A nonce with no analytics (e.g. the floating-CTA dismiss script, #982 P2 a)
            // widens only script-src; the analytics-host img/connect stay off.
            if (!$analytics->isEnabled()) {
                return "default-src 'self'; "
                    . "script-src {$scriptSrc}; "
                    . "style-src 'self' 'unsafe-inline'; "
                    . "font-src 'self' data:; "
                    . "img-src 'self' data:";
            }

            return "default-src 'self'; "
                . "script-src {$scriptSrc}; "
                . "style-src 'self' 'unsafe-inline'; "
                . "font-src 'self' data:; "
                . "img-src 'self' data: https://www.googletagmanager.com https://www.google-analytics.com https://*.google-analytics.com; "
                . "connect-src 'self' https://www.googletagmanager.com https://www.google-analytics.com https://*.google-analytics.com https://*.analytics.google.com";
        }

        // ── Embed path: compose 'self' + (analytics, if any) + embed origins ──
        $embedList = implode(' ', $embedOrigins);

        $script = "'self'";
        if ($hasNonce) {
            $script .= " 'nonce-{$scriptNonce}'";
        }
        if ($analytics->isEnabled()) {
            $script .= ' https://www.googletagmanager.com';
        }
        $script .= " {$embedList}";

        $img = "'self' data:";
        if ($analytics->isEnabled()) {
            $img .= ' https://www.googletagmanager.com https://www.google-analytics.com https://*.google-analytics.com';
        }

        $connect = "'self'";
        if ($analytics->isEnabled()) {
            $connect .= ' https://www.googletagmanager.com https://www.google-analytics.com https://*.google-analytics.com https://*.analytics.google.com';
        }
        $connect .= " {$embedList}";

        return "default-src 'self'; "
            . "script-src {$script}; "
            . "style-src 'self' 'unsafe-inline'; "
            . "font-src 'self' data:; "
            . "img-src {$img}; "
            . "connect-src {$connect}; "
            . "frame-src 'self' {$embedList}";
    }
}
