<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Http;

use NeNeRecords\Http\EmbedAllowlist;
use NeNeRecords\Http\PublicHtmlCsp;
use NeNeRecords\Http\WebAnalyticsConfig;
use PHPUnit\Framework\TestCase;

final class PublicHtmlCspTest extends TestCase
{
    public function testDisabledAnalyticsReturnsStrictBaseline(): void
    {
        $csp = PublicHtmlCsp::build(WebAnalyticsConfig::disabled(), 'noncevalue');

        self::assertSame(PublicHtmlCsp::POLICY, $csp);
        self::assertStringNotContainsString('googletagmanager', $csp);
        self::assertStringNotContainsString('nonce-', $csp);
        // Scripts stay 'self' (inherited from default-src) — no script-src directive.
        self::assertStringNotContainsString('script-src', $csp);
    }

    public function testEnabledAddsNonceAndTagManagerToScriptSrc(): void
    {
        $csp = PublicHtmlCsp::build(new WebAnalyticsConfig(null, 'G-XYZ987', 'denied'), 'abc123nonce');

        self::assertStringContainsString("script-src 'self' 'nonce-abc123nonce' https://www.googletagmanager.com", $csp);
    }

    public function testEnabledAddsAnalyticsHostsToConnectAndImg(): void
    {
        $csp = PublicHtmlCsp::build(new WebAnalyticsConfig('GTM-ABC1234', null, 'denied'), 'n0nce');

        self::assertStringContainsString('connect-src', $csp);
        self::assertStringContainsString('https://www.google-analytics.com', $csp);
        self::assertStringContainsString('https://*.analytics.google.com', $csp);
        self::assertStringContainsString('img-src', $csp);
        // SPA still needs its own origin for API/XHR.
        self::assertStringContainsString("connect-src 'self'", $csp);
    }

    public function testEnabledWithoutNonceStillAllowsTagManager(): void
    {
        $csp = PublicHtmlCsp::build(new WebAnalyticsConfig(null, 'G-XYZ987', 'denied'), null);

        self::assertStringContainsString('https://www.googletagmanager.com', $csp);
        self::assertStringNotContainsString('nonce-', $csp);
    }

    public function testBaselineRelaxesOnlyStyleFontImg(): void
    {
        // Guards the existing SPA contract: inline styles + data: fonts allowed,
        // scripts not loosened.
        self::assertStringContainsString("style-src 'self' 'unsafe-inline'", PublicHtmlCsp::POLICY);
        self::assertStringContainsString("font-src 'self' data:", PublicHtmlCsp::POLICY);
        self::assertStringNotContainsString("'unsafe-inline'", explode('style-src', PublicHtmlCsp::POLICY)[0]);
    }

    // ── Trusted-embed allowlist (#802) ────────────────────────────────────────

    /** @param list<string> $origins */
    private static function embeds(array $origins): EmbedAllowlist
    {
        return EmbedAllowlist::fromSettings(['embed_allowlist' => (string) json_encode($origins)]);
    }

    public function testEmptyAllowlistIsByteForByteIdenticalWhenAnalyticsDisabled(): void
    {
        // The whole guarantee of the feature: an empty allowlist changes nothing.
        self::assertSame(
            PublicHtmlCsp::POLICY,
            PublicHtmlCsp::build(WebAnalyticsConfig::disabled(), 'nonce', self::embeds([])),
        );
        self::assertSame(
            PublicHtmlCsp::build(WebAnalyticsConfig::disabled(), 'nonce'),
            PublicHtmlCsp::build(WebAnalyticsConfig::disabled(), 'nonce', self::embeds([])),
        );
    }

    public function testEmptyAllowlistIsByteForByteIdenticalWhenAnalyticsEnabled(): void
    {
        $analytics = new WebAnalyticsConfig('GTM-ABC1234', 'G-XYZ987', 'granted');

        self::assertSame(
            PublicHtmlCsp::build($analytics, 'nn'),
            PublicHtmlCsp::build($analytics, 'nn', self::embeds([])),
        );
        // A null EmbedAllowlist (default arg) is also a no-op.
        self::assertSame(
            PublicHtmlCsp::build($analytics, 'nn'),
            PublicHtmlCsp::build($analytics, 'nn', null),
        );
    }

    public function testAllowlistAddsOriginsToScriptConnectFrameOnly(): void
    {
        $csp = PublicHtmlCsp::build(
            WebAnalyticsConfig::disabled(),
            null,
            self::embeds(['https://contact.example.com']),
        );

        self::assertStringContainsString("script-src 'self' https://contact.example.com", $csp);
        self::assertStringContainsString("connect-src 'self' https://contact.example.com", $csp);
        self::assertStringContainsString("frame-src 'self' https://contact.example.com", $csp);
        // Not widened where an embed has no business: style/font/img/default stay strict.
        self::assertStringContainsString("default-src 'self';", $csp);
        self::assertStringContainsString("style-src 'self' 'unsafe-inline'", $csp);
        self::assertStringNotContainsString('contact.example.com', explode('script-src', $csp)[0]);
    }

    public function testAllowlistComposesWithAnalytics(): void
    {
        $csp = PublicHtmlCsp::build(
            new WebAnalyticsConfig(null, 'G-XYZ987', 'denied'),
            'abc',
            self::embeds(['https://contact.example.com']),
        );

        // Both the GA host and the embed origin ride script-src / connect-src.
        self::assertStringContainsString('https://www.googletagmanager.com', $csp);
        self::assertStringContainsString("'nonce-abc'", $csp);
        self::assertStringContainsString('https://contact.example.com', explode('script-src', $csp)[1]);
        self::assertStringContainsString('https://*.analytics.google.com', $csp);
        self::assertStringContainsString('frame-src', $csp);
    }
}
