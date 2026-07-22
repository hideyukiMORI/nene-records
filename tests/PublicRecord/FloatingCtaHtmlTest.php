<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PublicRecord;

use NeNeRecords\PublicRecord\FloatingCta;
use NeNeRecords\PublicRecord\FloatingCtaHtml;
use PHPUnit\Framework\TestCase;

final class FloatingCtaHtmlTest extends TestCase
{
    /** @param array<string, mixed> $cfg */
    private static function cta(array $cfg): FloatingCta
    {
        return FloatingCta::fromSettings(['floating_cta' => (string) json_encode($cfg + ['enabled' => true])]);
    }

    public function testDisabledRendersEmpty(): void
    {
        self::assertSame('', FloatingCtaHtml::render(FloatingCta::disabled(), 'page', '/'));
    }

    public function testNoMatchRendersEmpty(): void
    {
        $cta = self::cta([
            'content' => ['label' => 'Book'], 'link' => ['url' => 'https://x.test'],
            'conditions' => ['types' => ['post']],
        ]);
        self::assertSame('', FloatingCtaHtml::render($cta, 'page', '/services'));
    }

    public function testRendersButtonWithStyleAndLink(): void
    {
        $cta = self::cta([
            'accent' => '#D64525',
            'content' => ['icon' => '📅', 'label' => 'Book now', 'sub' => 'Online'],
            'link' => ['url' => 'https://calendar.app.google/x', 'newTab' => true],
        ]);
        $html = FloatingCtaHtml::render($cta, 'page', '/');

        self::assertStringContainsString('<style>', $html);
        self::assertStringContainsString('.nene-fab{position:fixed', $html);
        self::assertStringContainsString('#D64525', $html);
        self::assertStringContainsString('href="https://calendar.app.google/x"', $html);
        self::assertStringContainsString('target="_blank"', $html);
        self::assertStringContainsString('rel="noopener noreferrer"', $html);
        self::assertStringContainsString('Book now', $html);
        self::assertStringContainsString('Online', $html);
        self::assertStringContainsString('📅', $html);
        // CSS-only (P1): no script.
        self::assertStringNotContainsString('<script', $html);
    }

    public function testPositionBlUsesLeft(): void
    {
        $cta = self::cta(['position' => 'bl', 'content' => ['label' => 'x'], 'link' => ['url' => 'https://x.test']]);
        $html = FloatingCtaHtml::render($cta, 'page', '/');
        self::assertStringContainsString('left:calc(env(safe-area-inset-left', $html);
    }

    public function testBottomOffsetEmitsFooterClearance(): void
    {
        $cta = self::cta([
            'content' => ['label' => 'x'], 'link' => ['url' => 'https://x.test'],
            'bottomOffset' => 120,
        ]);
        $html = FloatingCtaHtml::render($cta, 'page', '/');
        self::assertStringContainsString('body{padding-bottom:calc(env(safe-area-inset-bottom,0px) + 120px)}', $html);
    }

    public function testNoBottomOffsetOmitsClearance(): void
    {
        $cta = self::cta(['content' => ['label' => 'x'], 'link' => ['url' => 'https://x.test']]);
        $html = FloatingCtaHtml::render($cta, 'page', '/');
        self::assertStringNotContainsString('body{padding-bottom', $html);
    }

    public function testEscapesAdminSuppliedText(): void
    {
        $cta = self::cta([
            'content' => ['label' => '<img src=x onerror=alert(1)>"quote'],
            'link' => ['url' => 'https://x.test'],
        ]);
        $html = FloatingCtaHtml::render($cta, 'page', '/');
        self::assertStringNotContainsString('<img src=x', $html);
        self::assertStringContainsString('&lt;img', $html);
        self::assertStringContainsString('&quot;quote', $html);
    }

    public function testNoNewTabOmitsTargetAndRel(): void
    {
        $cta = self::cta([
            'content' => ['label' => 'x'], 'link' => ['url' => '/contact', 'newTab' => false],
        ]);
        $html = FloatingCtaHtml::render($cta, 'page', '/');
        self::assertStringContainsString('href="/contact"', $html);
        self::assertStringNotContainsString('target="_blank"', $html);
        self::assertStringNotContainsString('rel="noopener', $html);
    }

    public function testCuratedIconIdRendersSvg(): void
    {
        $cta = self::cta([
            'content' => ['label' => 'Book', 'iconId' => 'calendar'],
            'link' => ['url' => 'https://x.test'],
        ]);
        $html = FloatingCtaHtml::render($cta, 'page', '/');
        self::assertStringContainsString('<span class="nene-fab__icon"', $html);
        self::assertStringContainsString('<svg width="18" height="18" viewBox="0 0 24 24"', $html);
        self::assertStringContainsString('stroke="currentColor"', $html);
    }

    public function testIconIdTakesPriorityOverEmoji(): void
    {
        $cta = self::cta([
            'content' => ['icon' => '📅', 'label' => 'Book', 'iconId' => 'video'],
            'link' => ['url' => 'https://x.test'],
        ]);
        $html = FloatingCtaHtml::render($cta, 'page', '/');
        self::assertStringContainsString('<svg', $html);
        // The emoji is dropped in favour of the curated svg.
        self::assertStringNotContainsString('📅', $html);
    }

    public function testInvalidIconIdFallsBackToEmoji(): void
    {
        $cta = self::cta([
            'content' => ['icon' => '📅', 'label' => 'Book', 'iconId' => 'bogus'],
            'link' => ['url' => 'https://x.test'],
        ]);
        $html = FloatingCtaHtml::render($cta, 'page', '/');
        self::assertStringNotContainsString('<svg', $html);
        self::assertStringContainsString('📅', $html);
    }
}
