<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PublicRecord;

use NeNeRecords\Http\EmbedAllowlist;
use NeNeRecords\PublicRecord\TrustedEmbedScripts;
use NeNeRecords\Widget\Widget;
use PHPUnit\Framework\TestCase;

final class TrustedEmbedScriptsTest extends TestCase
{
    /** @param array<string, mixed> $settings */
    private function widget(string $type, array $settings): Widget
    {
        return new Widget(
            id: 1,
            widgetType: $type,
            region: 'footer',
            displayOrder: 0,
            title: null,
            settings: $settings,
            createdAt: '',
            updatedAt: '',
        );
    }

    private function allowlist(string $origin = 'https://widgets.example.com'): EmbedAllowlist
    {
        return EmbedAllowlist::fromSettings(['embed_allowlist' => json_encode([$origin], JSON_THROW_ON_ERROR)]);
    }

    /** @return array<string, mixed> */
    private function validSettings(): array
    {
        return [
            'origin' => 'https://widgets.example.com',
            'src' => 'https://widgets.example.com/form.js',
            'integrity' => 'sha384-abcDEF123+/=',
        ];
    }

    public function testEmptyAllowlistRendersNothing(): void
    {
        $widgets = [$this->widget('trusted-embed', $this->validSettings())];

        self::assertSame('', TrustedEmbedScripts::render($widgets, EmbedAllowlist::empty()));
    }

    public function testNoTrustedEmbedWidgetsRendersNothing(): void
    {
        $widgets = [$this->widget('recent-posts', ['limit' => 5])];

        self::assertSame('', TrustedEmbedScripts::render($widgets, $this->allowlist()));
    }

    public function testAllowlistedEmbedRendersScriptTag(): void
    {
        $html = TrustedEmbedScripts::render(
            [$this->widget('trusted-embed', $this->validSettings())],
            $this->allowlist(),
        );

        self::assertStringContainsString('<script', $html);
        self::assertStringContainsString('src="https://widgets.example.com/form.js"', $html);
        self::assertStringContainsString('integrity="sha384-abcDEF123+/="', $html);
        self::assertStringContainsString('crossorigin="anonymous"', $html);
        self::assertStringContainsString('async', $html);
    }

    public function testOriginNotOnAllowlistIsRefused(): void
    {
        // Widget is structurally valid, but its origin is not the allowlisted one.
        $html = TrustedEmbedScripts::render(
            [$this->widget('trusted-embed', $this->validSettings())],
            $this->allowlist('https://other.example.org'),
        );

        self::assertSame('', $html);
    }

    public function testMissingSriIsRefused(): void
    {
        $settings = $this->validSettings();
        unset($settings['integrity']);

        $html = TrustedEmbedScripts::render(
            [$this->widget('trusted-embed', $settings)],
            $this->allowlist(),
        );

        self::assertSame('', $html);
    }

    public function testCrossOriginSrcIsRefused(): void
    {
        $settings = $this->validSettings();
        $settings['src'] = 'https://evil.example.net/x.js';

        $html = TrustedEmbedScripts::render(
            [$this->widget('trusted-embed', $settings)],
            $this->allowlist(),
        );

        self::assertSame('', $html);
    }

    public function testDataAttributeValuesAreEscaped(): void
    {
        $settings = $this->validSettings();
        $settings['attributes'] = ['data-note' => '"><img src=x onerror=alert(1)>'];

        $html = TrustedEmbedScripts::render(
            [$this->widget('trusted-embed', $settings)],
            $this->allowlist(),
        );

        self::assertStringContainsString('data-note="', $html);
        self::assertStringNotContainsString('<img', $html);
        self::assertStringContainsString('&lt;img', $html);
    }
}
