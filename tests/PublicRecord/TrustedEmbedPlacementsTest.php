<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PublicRecord;

use NeNeRecords\Http\EmbedAllowlist;
use NeNeRecords\PublicRecord\PublicHtmlSanitizer;
use NeNeRecords\PublicRecord\TrustedEmbedPlacements;
use NeNeRecords\Widget\Widget;
use PHPUnit\Framework\TestCase;

final class TrustedEmbedPlacementsTest extends TestCase
{
    private const ORIGIN = 'https://widgets.example.com';

    /** @param array<string, mixed> $settings */
    private function widget(
        int $id,
        string $type = 'trusted-embed',
        string $region = 'inline',
        ?array $settings = null,
    ): Widget {
        return new Widget(
            id: $id,
            widgetType: $type,
            region: $region,
            displayOrder: 0,
            title: null,
            settings: $settings ?? $this->validSettings(),
            createdAt: '',
            updatedAt: '',
        );
    }

    /** @return array<string, mixed> */
    private function validSettings(): array
    {
        return [
            'origin' => self::ORIGIN,
            'src' => self::ORIGIN . '/form.js',
            'integrity' => 'sha384-abcDEF123+/=',
        ];
    }

    private function allowlist(string $origin = self::ORIGIN): EmbedAllowlist
    {
        return EmbedAllowlist::fromSettings(['embed_allowlist' => json_encode([$origin], JSON_THROW_ON_ERROR)]);
    }

    private function marker(int $id = 1): string
    {
        return '<div data-nene-embed="' . $id . '"></div>';
    }

    // ---------------------------------------------------------------- happy path

    public function testMarkerIsReplacedWithTheValidatedTag(): void
    {
        $html = TrustedEmbedPlacements::apply(
            '<p>before</p>' . $this->marker() . '<p>after</p>',
            [$this->widget(1)],
            $this->allowlist(),
        );

        self::assertStringContainsString('<p>before</p>', $html);
        self::assertStringContainsString('<p>after</p>', $html);
        self::assertStringContainsString('<noscript data-nene-trusted-embed>', $html);
        self::assertStringContainsString('src="' . self::ORIGIN . '/form.js"', $html);
        self::assertStringContainsString('integrity="sha384-abcDEF123+/="', $html);
        self::assertStringContainsString('crossorigin="anonymous"', $html);
        self::assertStringNotContainsString('data-nene-embed', $html);
    }

    public function testTagIsEmittedAtTheMarkerPositionNotAppended(): void
    {
        $html = TrustedEmbedPlacements::apply(
            '<p>a</p>' . $this->marker() . '<p>b</p>',
            [$this->widget(1)],
            $this->allowlist(),
        );

        self::assertLessThan(strpos($html, '<p>b</p>') ?: 0, strpos($html, '<script') ?: 0);
        self::assertGreaterThan(strpos($html, '<p>a</p>') ?: 0, strpos($html, '<script') ?: 0);
    }

    public function testMarkerWithOtherAttributesStillResolves(): void
    {
        $html = TrustedEmbedPlacements::apply(
            '<div style="text-align:center" data-nene-embed="1"></div>',
            [$this->widget(1)],
            $this->allowlist(),
        );

        self::assertStringContainsString('<script', $html);
    }

    public function testDataAttributesFromSettingsAreEmitted(): void
    {
        $settings = $this->validSettings();
        $settings['attributes'] = ['data-form' => 'ayane-contact', 'data-trigger' => 'button'];

        $html = TrustedEmbedPlacements::apply(
            $this->marker(),
            [$this->widget(1, settings: $settings)],
            $this->allowlist(),
        );

        self::assertStringContainsString('data-form="ayane-contact"', $html);
        self::assertStringContainsString('data-trigger="button"', $html);
    }

    // ---------------------------------------------------------------- fail closed

    public function testUnknownWidgetIdRemovesTheMarker(): void
    {
        $html = TrustedEmbedPlacements::apply(
            '<p>a</p>' . $this->marker(99) . '<p>b</p>',
            [$this->widget(1)],
            $this->allowlist(),
        );

        self::assertSame('<p>a</p><p>b</p>', $html);
    }

    public function testEmptyAllowlistRemovesTheMarker(): void
    {
        self::assertSame(
            '',
            TrustedEmbedPlacements::apply($this->marker(), [$this->widget(1)], EmbedAllowlist::empty()),
        );
    }

    public function testOriginOutsideTheAllowlistRemovesTheMarker(): void
    {
        self::assertSame(
            '',
            TrustedEmbedPlacements::apply(
                $this->marker(),
                [$this->widget(1)],
                $this->allowlist('https://other.example.com'),
            ),
        );
    }

    public function testNonTrustedEmbedWidgetRemovesTheMarker(): void
    {
        self::assertSame(
            '',
            TrustedEmbedPlacements::apply(
                $this->marker(),
                [$this->widget(1, type: 'recent-posts', settings: ['limit' => 5])],
                $this->allowlist(),
            ),
        );
    }

    public function testRegionPlacedWidgetIsNotPlaceableByMarker(): void
    {
        // It already renders in its region; honouring the marker would double-load.
        self::assertSame(
            '',
            TrustedEmbedPlacements::apply(
                $this->marker(),
                [$this->widget(1, region: 'footer')],
                $this->allowlist(),
            ),
        );
    }

    public function testMalformedSettingsRemoveTheMarker(): void
    {
        $settings = $this->validSettings();
        unset($settings['integrity']);

        self::assertSame(
            '',
            TrustedEmbedPlacements::apply($this->marker(), [$this->widget(1, settings: $settings)], $this->allowlist()),
        );
    }

    public function testCrossOriginSrcRemovesTheMarker(): void
    {
        $settings = $this->validSettings();
        $settings['src'] = 'https://evil.example.com/form.js';

        self::assertSame(
            '',
            TrustedEmbedPlacements::apply($this->marker(), [$this->widget(1, settings: $settings)], $this->allowlist()),
        );
    }

    // ---------------------------------------------------------------- shape rules

    public function testTheSameWidgetIsEmittedOnlyOncePerDocument(): void
    {
        $html = TrustedEmbedPlacements::apply(
            $this->marker() . '<p>x</p>' . $this->marker(),
            [$this->widget(1)],
            $this->allowlist(),
        );

        self::assertSame(1, substr_count($html, '<script'));
    }

    public function testMarkerWithContentIsNotAPlacement(): void
    {
        $input = '<div data-nene-embed="1">x</div>';

        self::assertSame($input, TrustedEmbedPlacements::apply($input, [$this->widget(1)], $this->allowlist()));
    }

    public function testHtmlWithoutAnyMarkerIsReturnedByteForByte(): void
    {
        $input = '<p>plain <em>body</em></p><img src="/a.png" alt="a">';

        self::assertSame($input, TrustedEmbedPlacements::apply($input, [$this->widget(1)], $this->allowlist()));
    }

    public function testMarkerInsideAnAttributeValueIsNotSubstituted(): void
    {
        // The sanitizer escapes `<` inside attribute values, so a marker written
        // there is inert text — it must not become a script.
        $input = '<p title="&lt;div data-nene-embed=&quot;1&quot;&gt;&lt;/div&gt;">t</p>';

        self::assertSame($input, TrustedEmbedPlacements::apply($input, [$this->widget(1)], $this->allowlist()));
    }

    // ---------------------------------------------------------------- with the sanitizer

    public function testSanitizerKeepsTheMarkerButStillStripsScripts(): void
    {
        $sanitized = (new PublicHtmlSanitizer())->sanitize(
            '<p>a</p>' . $this->marker() . '<script>alert(1)</script><p onclick="x()">b</p>',
        );

        self::assertStringContainsString('data-nene-embed="1"', $sanitized);
        self::assertStringNotContainsString('alert(1)', $sanitized);
        self::assertStringNotContainsString('onclick', $sanitized);

        $html = TrustedEmbedPlacements::apply($sanitized, [$this->widget(1)], $this->allowlist());

        self::assertStringContainsString('<script src="' . self::ORIGIN . '/form.js"', $html);
        self::assertStringNotContainsString('alert(1)', $html);
    }

    public function testMarkerAttributeIsNotHonouredOnOtherElements(): void
    {
        $sanitized = (new PublicHtmlSanitizer())->sanitize('<span data-nene-embed="1"></span>');

        self::assertStringNotContainsString('data-nene-embed', $sanitized);
        self::assertStringNotContainsString(
            '<script',
            TrustedEmbedPlacements::apply($sanitized, [$this->widget(1)], $this->allowlist()),
        );
    }

    public function testAuthoredScriptTagIsStillStrippedEvenWithAnAllowlistedSrc(): void
    {
        // The concession is the marker attribute — never a raw <script> in content.
        $sanitized = (new PublicHtmlSanitizer())->sanitize(
            '<script src="' . self::ORIGIN . '/form.js" integrity="sha384-abcDEF123+/=" crossorigin="anonymous"></script>',
        );

        self::assertSame('', trim($sanitized));
    }
}
