<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Http;

use NeNeRecords\Http\WebAnalyticsConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class WebAnalyticsConfigTest extends TestCase
{
    public function testDisabledFactoryHasNoIds(): void
    {
        $config = WebAnalyticsConfig::disabled();

        self::assertFalse($config->isEnabled());
        self::assertNull($config->gtmId);
        self::assertNull($config->ga4Id);
        self::assertSame('denied', $config->consentDefault);
    }

    public function testEmptySettingsAreDisabled(): void
    {
        $config = WebAnalyticsConfig::fromSettings([]);

        self::assertFalse($config->isEnabled());
    }

    public function testValidGtmIdEnables(): void
    {
        $config = WebAnalyticsConfig::fromSettings(['analytics_gtm_id' => 'GTM-ABC1234']);

        self::assertTrue($config->isEnabled());
        self::assertSame('GTM-ABC1234', $config->gtmId);
        self::assertNull($config->ga4Id);
    }

    public function testValidGa4IdEnables(): void
    {
        $config = WebAnalyticsConfig::fromSettings(['analytics_ga4_id' => 'G-XYZ987']);

        self::assertTrue($config->isEnabled());
        self::assertSame('G-XYZ987', $config->ga4Id);
        self::assertNull($config->gtmId);
    }

    public function testWhitespaceAroundIdIsTrimmed(): void
    {
        $config = WebAnalyticsConfig::fromSettings(['analytics_ga4_id' => '  G-XYZ987  ']);

        self::assertSame('G-XYZ987', $config->ga4Id);
    }

    /**
     * Anything outside `[A-Za-z0-9_-]{4,40}` is treated as "not set" — this both
     * gates the feature and removes the CSP-header / inline-script injection surface.
     */
    #[DataProvider('invalidIds')]
    public function testInvalidIdsAreRejected(string $raw): void
    {
        $config = WebAnalyticsConfig::fromSettings(['analytics_gtm_id' => $raw, 'analytics_ga4_id' => $raw]);

        self::assertFalse($config->isEnabled(), 'invalid id must not enable analytics');
        self::assertNull($config->gtmId);
        self::assertNull($config->ga4Id);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidIds(): iterable
    {
        yield 'empty' => [''];
        yield 'too short' => ['G-1'];
        yield 'quote injection' => ["G-1';alert(1)//"];
        yield 'angle bracket' => ['G-<script>'];
        yield 'space' => ['G ABC'];
        yield 'semicolon (csp break)' => ['GTM-1; script-src *'];
        yield 'too long' => [str_repeat('A', 41)];
    }

    public function testConsentDefaultGranted(): void
    {
        $config = WebAnalyticsConfig::fromSettings([
            'analytics_ga4_id' => 'G-XYZ987',
            'analytics_consent_default' => 'granted',
        ]);

        self::assertSame('granted', $config->consentDefault);
    }

    public function testConsentDefaultFallsBackToDenied(): void
    {
        foreach (['', 'yes', 'GRANTED', 'true', 'denied'] as $raw) {
            $config = WebAnalyticsConfig::fromSettings([
                'analytics_ga4_id' => 'G-XYZ987',
                'analytics_consent_default' => $raw,
            ]);

            self::assertSame('denied', $config->consentDefault, "\"{$raw}\" must normalize to denied");
        }
    }

    public function testGtmAndGa4CanCoexist(): void
    {
        $config = WebAnalyticsConfig::fromSettings([
            'analytics_gtm_id' => 'GTM-ABC1234',
            'analytics_ga4_id' => 'G-XYZ987',
        ]);

        self::assertTrue($config->isEnabled());
        self::assertSame('GTM-ABC1234', $config->gtmId);
        self::assertSame('G-XYZ987', $config->ga4Id);
    }
}
