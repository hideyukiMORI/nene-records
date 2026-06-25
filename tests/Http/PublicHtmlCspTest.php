<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Http;

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
}
