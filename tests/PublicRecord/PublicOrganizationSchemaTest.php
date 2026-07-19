<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PublicRecord;

use NeNeRecords\PublicRecord\PublicOrganizationSchema;
use PHPUnit\Framework\TestCase;

final class PublicOrganizationSchemaTest extends TestCase
{
    public function testMinimalWhenNoOptionalSettings(): void
    {
        $org = PublicOrganizationSchema::build('彩音インターナショナル株式会社', 'https://ayane.co.jp/', null, []);

        self::assertSame([
            '@type' => 'Organization',
            'name' => '彩音インターナショナル株式会社',
            'url' => 'https://ayane.co.jp/',
        ], $org);
    }

    public function testLogoAddedWhenProvided(): void
    {
        $org = PublicOrganizationSchema::build('Acme', 'https://acme.test/', 'https://acme.test/media/2026/07/logo.png', []);

        self::assertSame('https://acme.test/media/2026/07/logo.png', $org['logo']);
    }

    public function testSameAsFromFooterSocialLinks(): void
    {
        $settings = ['footer_config' => json_encode([
            'social' => [
                ['platform' => 'x', 'url' => 'https://twitter.com/ayane_inc'],
                ['platform' => 'facebook', 'url' => 'https://www.facebook.com/AyaneInternational'],
            ],
        ], JSON_THROW_ON_ERROR)];

        $org = PublicOrganizationSchema::build('Acme', 'https://acme.test/', null, $settings);

        self::assertSame([
            'https://twitter.com/ayane_inc',
            'https://www.facebook.com/AyaneInternational',
        ], $org['sameAs']);
    }

    public function testSameAsDedupesAndSkipsBlankUrls(): void
    {
        $settings = ['footer_config' => json_encode([
            'social' => [
                ['platform' => 'x', 'url' => 'https://x.test/a'],
                ['platform' => 'y', 'url' => ''],
                ['platform' => 'z', 'url' => 'https://x.test/a'],
            ],
        ], JSON_THROW_ON_ERROR)];

        $org = PublicOrganizationSchema::build('Acme', 'https://acme.test/', null, $settings);

        self::assertSame(['https://x.test/a'], $org['sameAs']);
    }

    public function testNoSameAsKeyWhenNoSocialLinks(): void
    {
        $settings = ['footer_config' => json_encode(['social' => []], JSON_THROW_ON_ERROR)];

        $org = PublicOrganizationSchema::build('Acme', 'https://acme.test/', null, $settings);

        self::assertArrayNotHasKey('sameAs', $org);
    }

    public function testContactPointFromHeaderTopbar(): void
    {
        $settings = ['header_config' => json_encode([
            'topbar' => ['enabled' => true, 'phone' => '03-1234-5678', 'email' => 'info@ayane.co.jp', 'infoText' => ''],
        ], JSON_THROW_ON_ERROR)];

        $org = PublicOrganizationSchema::build('Acme', 'https://acme.test/', null, $settings);

        self::assertSame([
            '@type' => 'ContactPoint',
            'contactType' => 'sales',
            'email' => 'info@ayane.co.jp',
            'telephone' => '03-1234-5678',
        ], $org['contactPoint']);
    }

    public function testContactPointOmittedWhenEmailAndPhoneBlank(): void
    {
        $settings = ['header_config' => json_encode([
            'topbar' => ['enabled' => false, 'phone' => '', 'email' => '', 'infoText' => ''],
        ], JSON_THROW_ON_ERROR)];

        $org = PublicOrganizationSchema::build('Acme', 'https://acme.test/', null, $settings);

        self::assertArrayNotHasKey('contactPoint', $org);
    }

    public function testMalformedConfigJsonIsIgnored(): void
    {
        $settings = ['footer_config' => '{not json', 'header_config' => 'also bad'];

        $org = PublicOrganizationSchema::build('Acme', 'https://acme.test/', null, $settings);

        self::assertArrayNotHasKey('sameAs', $org);
        self::assertArrayNotHasKey('contactPoint', $org);
    }
}
