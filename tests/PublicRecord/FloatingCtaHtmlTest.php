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
        self::assertStringContainsString('.nene-fab-wrap{position:fixed', $html);
        self::assertStringContainsString('<div class="nene-fab-wrap" id="nene-fab-wrap">', $html);
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

    public function testDismissibleWithNonceEmitsButtonAndNoncedScript(): void
    {
        $cta = self::cta([
            'content' => ['label' => 'x'], 'link' => ['url' => 'https://x.test'],
            'dismissible' => true,
        ]);
        $html = FloatingCtaHtml::render($cta, 'page', '/', 'abc123', 'ja');

        self::assertStringContainsString('class="nene-fab__dismiss"', $html);
        self::assertStringContainsString('aria-label="閉じる"', $html);
        self::assertStringContainsString('<script nonce="abc123">', $html);
        self::assertStringContainsString("localStorage.setItem(k,'1')", $html);
        self::assertStringContainsString("localStorage.getItem(k)==='1'", $html);
        self::assertStringContainsString('.nene-fab__dismiss{position:absolute', $html);
    }

    public function testDismissibleWithoutNonceOmitsScript(): void
    {
        // No nonce available (analytics off, renderer supplies none) → no dismiss UI, but a
        // fully working FAB still renders (graceful degradation, no CSP violation).
        $cta = self::cta([
            'content' => ['label' => 'x'], 'link' => ['url' => 'https://x.test'],
            'dismissible' => true,
        ]);
        $html = FloatingCtaHtml::render($cta, 'page', '/');

        self::assertStringNotContainsString('<script', $html);
        self::assertStringNotContainsString('nene-fab__dismiss', $html);
        self::assertStringContainsString('class="nene-fab"', $html);
    }

    public function testNonDismissibleNeverEmitsScriptEvenWithNonce(): void
    {
        $cta = self::cta(['content' => ['label' => 'x'], 'link' => ['url' => 'https://x.test']]);
        $html = FloatingCtaHtml::render($cta, 'page', '/', 'abc123', 'en');

        self::assertStringNotContainsString('<script', $html);
        self::assertStringNotContainsString('nene-fab__dismiss', $html);
    }

    public function testDelayTriggerEmitsPureCssRevealNoScript(): void
    {
        $cta = self::cta([
            'content' => ['label' => 'x'], 'link' => ['url' => 'https://x.test'],
            'trigger' => 'delay', 'triggerValue' => 5,
        ]);
        $html = FloatingCtaHtml::render($cta, 'page', '/');

        self::assertStringContainsString('@keyframes nene-fab-appear', $html);
        self::assertStringContainsString('animation:nene-fab-appear .35s ease both;animation-delay:5s', $html);
        // Pure CSS — no script / nonce for a delay-only FAB.
        self::assertStringNotContainsString('<script', $html);
    }

    public function testAlwaysTriggerOmitsDelayCss(): void
    {
        $cta = self::cta(['content' => ['label' => 'x'], 'link' => ['url' => 'https://x.test']]);
        $html = FloatingCtaHtml::render($cta, 'page', '/');
        self::assertStringNotContainsString('nene-fab-appear', $html);
        self::assertStringNotContainsString('animation-delay', $html);
    }

    public function testScrollTriggerHidesUntilNoncedScriptReveals(): void
    {
        $cta = self::cta([
            'content' => ['label' => 'x'], 'link' => ['url' => 'https://x.test'],
            'trigger' => 'scroll', 'triggerValue' => 400,
        ]);
        $html = FloatingCtaHtml::render($cta, 'page', '/', 'sc0ped', 'en');

        // Hidden until revealed; script carries the threshold and toggles .is-visible.
        self::assertStringContainsString('.nene-fab-wrap{opacity:0;pointer-events:none', $html);
        self::assertStringContainsString('.nene-fab-wrap.is-visible{opacity:1', $html);
        self::assertStringContainsString('<script nonce="sc0ped">', $html);
        self::assertStringContainsString('var t=400;', $html);
        self::assertStringContainsString("w.classList.add('is-visible')", $html);
        // No-JS visitors still get the FAB.
        self::assertStringContainsString('<noscript><style>.nene-fab-wrap{opacity:1;pointer-events:auto}</style></noscript>', $html);
        // Scroll-only (no dismiss config) → no × button element (the script still queries for one).
        self::assertStringNotContainsString('<button type="button" class="nene-fab__dismiss"', $html);
    }

    public function testScrollWithoutNonceFallsBackToAlwaysVisible(): void
    {
        // No nonce → the reveal script can't run, so the FAB must not be hidden (shows always).
        $cta = self::cta([
            'content' => ['label' => 'x'], 'link' => ['url' => 'https://x.test'],
            'trigger' => 'scroll', 'triggerValue' => 400,
        ]);
        $html = FloatingCtaHtml::render($cta, 'page', '/');

        self::assertStringNotContainsString('<script', $html);
        self::assertStringNotContainsString('opacity:0', $html);
        self::assertStringNotContainsString('<noscript', $html);
    }

    public function testScrollAndDismissShareOneScript(): void
    {
        $cta = self::cta([
            'content' => ['label' => 'x'], 'link' => ['url' => 'https://x.test'],
            'trigger' => 'scroll', 'triggerValue' => 250, 'dismissible' => true,
        ]);
        $html = FloatingCtaHtml::render($cta, 'page', '/', 'nn', 'en');

        self::assertSame(1, substr_count($html, '<script nonce="nn">'));
        self::assertStringContainsString('nene-fab__dismiss', $html);
        self::assertStringContainsString('var t=250;', $html);
        self::assertStringContainsString("localStorage.setItem(k,'1')", $html);
    }

    public function testDismissButtonSitsOnPositionSide(): void
    {
        $cta = self::cta([
            'position' => 'bl', 'content' => ['label' => 'x'], 'link' => ['url' => 'https://x.test'],
            'dismissible' => true,
        ]);
        $html = FloatingCtaHtml::render($cta, 'page', '/', 'n0nce', 'en');
        self::assertStringContainsString('.nene-fab__dismiss{position:absolute;top:-9px;left:-9px', $html);
        self::assertStringContainsString('aria-label="Close"', $html);
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
