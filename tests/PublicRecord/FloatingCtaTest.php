<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PublicRecord;

use NeNeRecords\PublicRecord\FloatingCta;
use PHPUnit\Framework\TestCase;

final class FloatingCtaTest extends TestCase
{
    /**
     * @param array<string, mixed> $cfg
     * @return array<string, string>
     */
    private static function settings(array $cfg): array
    {
        return ['floating_cta' => (string) json_encode($cfg)];
    }

    public function testMissingOrEmptyIsDisabled(): void
    {
        self::assertFalse(FloatingCta::fromSettings([])->enabled);
        self::assertFalse(FloatingCta::fromSettings(['floating_cta' => ''])->enabled);
        self::assertFalse(FloatingCta::fromSettings(['floating_cta' => 'not json'])->enabled);
    }

    public function testEnabledFalseIsDisabled(): void
    {
        self::assertFalse(FloatingCta::fromSettings(self::settings(['enabled' => false]))->enabled);
    }

    public function testEnabledWithoutLabelOrUrlIsDisabled(): void
    {
        self::assertFalse(FloatingCta::fromSettings(self::settings([
            'enabled' => true, 'content' => ['label' => ''], 'link' => ['url' => 'https://x.test'],
        ]))->enabled);
        self::assertFalse(FloatingCta::fromSettings(self::settings([
            'enabled' => true, 'content' => ['label' => 'x'], 'link' => ['url' => ''],
        ]))->enabled);
    }

    public function testUnsafeUrlIsDisabledOnRead(): void
    {
        // Belt-and-suspenders: even a hand-edited row with a javascript: url must not enable.
        self::assertFalse(FloatingCta::fromSettings(self::settings([
            'enabled' => true, 'content' => ['label' => 'x'], 'link' => ['url' => 'javascript:alert(1)'],
        ]))->enabled);
    }

    public function testValidConfigResolves(): void
    {
        $cta = FloatingCta::fromSettings(self::settings([
            'enabled' => true, 'position' => 'bl', 'accent' => '#D64525',
            'content' => ['icon' => '📅', 'label' => 'Book', 'sub' => 'Online'],
            'link' => ['url' => 'https://x.test', 'newTab' => true],
        ]));
        self::assertTrue($cta->enabled);
        self::assertSame('bl', $cta->position);
        self::assertSame('#D64525', $cta->accent);
        self::assertSame('Book', $cta->label);
    }

    public function testBottomOffsetIsParsedAndClamped(): void
    {
        $base = ['enabled' => true, 'content' => ['label' => 'x'], 'link' => ['url' => 'https://x.test']];

        self::assertSame(120, FloatingCta::fromSettings(self::settings($base + ['bottomOffset' => 120]))->bottomOffset);
        // Out of range / wrong type all resolve to 0 (no clearance).
        self::assertSame(FloatingCta::MAX_BOTTOM_OFFSET, FloatingCta::fromSettings(self::settings($base + ['bottomOffset' => 9999]))->bottomOffset);
        self::assertSame(0, FloatingCta::fromSettings(self::settings($base + ['bottomOffset' => -50]))->bottomOffset);
        self::assertSame(0, FloatingCta::fromSettings(self::settings($base + ['bottomOffset' => '80']))->bottomOffset);
        self::assertSame(0, FloatingCta::fromSettings(self::settings($base))->bottomOffset);
    }

    public function testDismissibleIsParsed(): void
    {
        $base = ['enabled' => true, 'content' => ['label' => 'x'], 'link' => ['url' => 'https://x.test']];
        self::assertFalse(FloatingCta::fromSettings(self::settings($base))->dismissible);
        self::assertTrue(FloatingCta::fromSettings(self::settings($base + ['dismissible' => true]))->dismissible);
        self::assertFalse(FloatingCta::fromSettings(self::settings($base + ['dismissible' => 'yes']))->dismissible);
    }

    public function testIsDismissActiveForRequiresEnabledDismissibleAndMatch(): void
    {
        $base = ['enabled' => true, 'dismissible' => true, 'content' => ['label' => 'x'], 'link' => ['url' => 'https://x.test']];

        self::assertTrue(FloatingCta::fromSettings(self::settings($base))->isDismissActiveFor('page', '/'));
        // Not dismissible → false even though it renders.
        self::assertFalse(FloatingCta::fromSettings(self::settings(['dismissible' => false] + $base))->isDismissActiveFor('page', '/'));
        // Page excluded → false even though dismissible.
        self::assertFalse(
            FloatingCta::fromSettings(self::settings($base + ['conditions' => ['exclude' => ['/secret*']]]))
                ->isDismissActiveFor('page', '/secret'),
        );
        // Disabled → false.
        self::assertFalse(FloatingCta::disabled()->isDismissActiveFor('page', '/'));
    }

    public function testInvalidPositionFallsBackToBr(): void
    {
        $cta = FloatingCta::fromSettings(self::settings([
            'enabled' => true, 'position' => 'top',
            'content' => ['label' => 'x'], 'link' => ['url' => 'https://x.test'],
        ]));
        self::assertSame('br', $cta->position);
    }

    public function testConditionsTypeGate(): void
    {
        $cta = FloatingCta::fromSettings(self::settings([
            'enabled' => true, 'content' => ['label' => 'x'], 'link' => ['url' => 'https://x.test'],
            'conditions' => ['types' => ['page']],
        ]));
        self::assertTrue($cta->shouldRender('page', '/services'));
        self::assertFalse($cta->shouldRender('post', '/blog/x'));
    }

    public function testConditionsUrlGlobIncludeAndExclude(): void
    {
        $cta = FloatingCta::fromSettings(self::settings([
            'enabled' => true, 'content' => ['label' => 'x'], 'link' => ['url' => 'https://x.test'],
            'conditions' => ['urlGlobs' => ['/services*'], 'exclude' => ['/services/secret']],
        ]));
        self::assertTrue($cta->shouldRender('page', '/services'));
        self::assertTrue($cta->shouldRender('page', '/services/pricing'));
        self::assertFalse($cta->shouldRender('page', '/services/secret'));
        self::assertFalse($cta->shouldRender('page', '/company'));
    }

    public function testExcludeWinsOverEmptyConditions(): void
    {
        $cta = FloatingCta::fromSettings(self::settings([
            'enabled' => true, 'content' => ['label' => 'x'], 'link' => ['url' => 'https://x.test'],
            'conditions' => ['exclude' => ['/admin*']],
        ]));
        self::assertTrue($cta->shouldRender('page', '/'));
        self::assertFalse($cta->shouldRender('page', '/admin/settings'));
    }

    public function testDisabledNeverRenders(): void
    {
        self::assertFalse(FloatingCta::disabled()->shouldRender('page', '/'));
    }
}
