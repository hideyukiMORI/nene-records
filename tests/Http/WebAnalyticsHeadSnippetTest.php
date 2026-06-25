<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Http;

use NeNeRecords\Http\WebAnalyticsConfig;
use NeNeRecords\Http\WebAnalyticsHeadSnippet;
use PHPUnit\Framework\TestCase;

final class WebAnalyticsHeadSnippetTest extends TestCase
{
    private const NONCE = 'deadbeefdeadbeefdeadbeefdeadbeef';

    public function testDisabledConfigRendersNothing(): void
    {
        self::assertSame('', WebAnalyticsHeadSnippet::render(WebAnalyticsConfig::disabled(), self::NONCE));
    }

    public function testGa4PathLoadsGtagAndConfigures(): void
    {
        $html = WebAnalyticsHeadSnippet::render(
            new WebAnalyticsConfig(null, 'G-XYZ987', 'denied'),
            self::NONCE,
        );

        self::assertStringContainsString('https://www.googletagmanager.com/gtag/js?id=G-XYZ987', $html);
        self::assertStringContainsString("gtag('config','G-XYZ987')", $html);
        self::assertStringNotContainsString('/gtm.js', $html);
    }

    public function testGtmPathLoadsContainerNotGtag(): void
    {
        $html = WebAnalyticsHeadSnippet::render(
            new WebAnalyticsConfig('GTM-ABC1234', null, 'denied'),
            self::NONCE,
        );

        self::assertStringContainsString("https://www.googletagmanager.com/gtm.js?id='+i", $html);
        self::assertStringContainsString('GTM-ABC1234', $html);
        // GTM orchestrates GA4 itself — no direct gtag.js loader.
        self::assertStringNotContainsString('/gtag/js', $html);
    }

    public function testGtmWinsWhenBothConfigured(): void
    {
        $html = WebAnalyticsHeadSnippet::render(
            new WebAnalyticsConfig('GTM-ABC1234', 'G-XYZ987', 'denied'),
            self::NONCE,
        );

        self::assertStringContainsString('/gtm.js', $html);
        self::assertStringNotContainsString('/gtag/js', $html);
    }

    public function testConsentDefaultDeniedIsEmitted(): void
    {
        $html = WebAnalyticsHeadSnippet::render(
            new WebAnalyticsConfig(null, 'G-XYZ987', 'denied'),
            self::NONCE,
        );

        self::assertStringContainsString("gtag('consent','default'", $html);
        self::assertStringContainsString("'analytics_storage':'denied'", $html);
        self::assertStringContainsString("'ad_storage':'denied'", $html);
        // security_storage is always granted regardless of the default.
        self::assertStringContainsString("'security_storage':'granted'", $html);
    }

    public function testConsentDefaultGrantedIsEmitted(): void
    {
        $html = WebAnalyticsHeadSnippet::render(
            new WebAnalyticsConfig(null, 'G-XYZ987', 'granted'),
            self::NONCE,
        );

        self::assertStringContainsString("'analytics_storage':'granted'", $html);
        self::assertStringContainsString("'ad_storage':'granted'", $html);
    }

    public function testEveryScriptTagCarriesTheNonce(): void
    {
        $html = WebAnalyticsHeadSnippet::render(
            new WebAnalyticsConfig(null, 'G-XYZ987', 'denied'),
            self::NONCE,
        );

        // Both the external loader and the inline init must be nonce'd to pass CSP.
        $scriptTags = substr_count($html, '<script');
        $noncedTags = substr_count($html, 'nonce="' . self::NONCE . '"');
        self::assertSame($scriptTags, $noncedTags, 'every <script> must carry the nonce');
        self::assertGreaterThanOrEqual(2, $scriptTags);
    }

    public function testConsentDefaultPrecedesLoader(): void
    {
        $html = WebAnalyticsHeadSnippet::render(
            new WebAnalyticsConfig('GTM-ABC1234', null, 'denied'),
            self::NONCE,
        );

        $consentPos = strpos($html, "gtag('consent','default'");
        $loaderPos = strpos($html, 'gtm.js');
        self::assertNotFalse($consentPos);
        self::assertNotFalse($loaderPos);
        self::assertLessThan($loaderPos, $consentPos, 'consent default must run before the loader');
    }
}
