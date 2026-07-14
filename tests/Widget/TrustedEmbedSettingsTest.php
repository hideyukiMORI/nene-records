<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Widget;

use NeNeRecords\Widget\TrustedEmbedSettings;
use PHPUnit\Framework\TestCase;

final class TrustedEmbedSettingsTest extends TestCase
{
    /** @return array<string, mixed> */
    private function valid(): array
    {
        return [
            'origin' => 'https://widgets.example.com',
            'src' => 'https://widgets.example.com/form.js',
            'integrity' => 'sha384-abcDEF123+/=',
        ];
    }

    public function testValidateAcceptsWellFormedEmbed(): void
    {
        self::assertSame([], TrustedEmbedSettings::validate($this->valid()));
    }

    public function testTryParseReturnsSpecForWellFormedEmbed(): void
    {
        $spec = TrustedEmbedSettings::tryParse($this->valid());

        self::assertNotNull($spec);
        self::assertSame('https://widgets.example.com', $spec->origin);
        self::assertSame('https://widgets.example.com/form.js', $spec->src);
        self::assertSame('sha384-abcDEF123+/=', $spec->integrity);
        self::assertSame([], $spec->attributes);
    }

    public function testMissingIntegrityIsRejected(): void
    {
        $settings = $this->valid();
        unset($settings['integrity']);

        $errors = TrustedEmbedSettings::validate($settings);
        self::assertContains('settings.integrity', array_map(static fn ($e) => $e->field, $errors));
        self::assertNull(TrustedEmbedSettings::tryParse($settings));
    }

    public function testNonHttpsOriginIsRejected(): void
    {
        $settings = $this->valid();
        $settings['origin'] = 'http://widgets.example.com';
        $settings['src'] = 'http://widgets.example.com/form.js';

        self::assertNotSame([], TrustedEmbedSettings::validate($settings));
        self::assertNull(TrustedEmbedSettings::tryParse($settings));
    }

    public function testWildcardOriginIsRejected(): void
    {
        $settings = $this->valid();
        $settings['origin'] = 'https://*.example.com';

        self::assertNotSame([], TrustedEmbedSettings::validate($settings));
        self::assertNull(TrustedEmbedSettings::tryParse($settings));
    }

    public function testCrossOriginSrcIsRejected(): void
    {
        $settings = $this->valid();
        $settings['src'] = 'https://evil.example.net/form.js';

        $errors = TrustedEmbedSettings::validate($settings);
        self::assertContains('origin_mismatch', array_map(static fn ($e) => $e->code, $errors));
        self::assertNull(TrustedEmbedSettings::tryParse($settings));
    }

    public function testMalformedIntegrityIsRejected(): void
    {
        $settings = $this->valid();
        $settings['integrity'] = 'md5-notallowed';

        self::assertNotSame([], TrustedEmbedSettings::validate($settings));
        self::assertNull(TrustedEmbedSettings::tryParse($settings));
    }

    public function testDataAttributesAreAcceptedAndOthersRejected(): void
    {
        $ok = $this->valid();
        $ok['attributes'] = ['data-form-id' => 'abc123', 'data-mode' => 'inline'];
        self::assertSame([], TrustedEmbedSettings::validate($ok));
        $spec = TrustedEmbedSettings::tryParse($ok);
        self::assertNotNull($spec);
        self::assertSame(['data-form-id' => 'abc123', 'data-mode' => 'inline'], $spec->attributes);

        $bad = $this->valid();
        $bad['attributes'] = ['onload' => 'alert(1)'];
        self::assertNotSame([], TrustedEmbedSettings::validate($bad));
        self::assertNull(TrustedEmbedSettings::tryParse($bad));
    }

    public function testSupportsMultipleSriHashes(): void
    {
        $settings = $this->valid();
        $settings['integrity'] = 'sha256-aaaa sha384-bbbb';

        self::assertSame([], TrustedEmbedSettings::validate($settings));
        self::assertNotNull(TrustedEmbedSettings::tryParse($settings));
    }
}
