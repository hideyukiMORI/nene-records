<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Theme;

use NeNeRecords\Theme\ColorContrast;
use PHPUnit\Framework\TestCase;

final class ColorContrastTest extends TestCase
{
    public function testHexBlackOnWhiteIsMaxContrast(): void
    {
        $ratio = ColorContrast::ratio('#000000', '#ffffff');
        self::assertNotNull($ratio);
        self::assertEqualsWithDelta(21.0, $ratio, 0.05);
    }

    public function testOklchWhiteAndBlackMatchHex(): void
    {
        $ratio = ColorContrast::ratio('oklch(0% 0 0)', 'oklch(100% 0 0)');
        self::assertNotNull($ratio);
        self::assertEqualsWithDelta(21.0, $ratio, 0.1);
    }

    public function testShorthandHexParses(): void
    {
        self::assertEqualsWithDelta(21.0, (float) ColorContrast::ratio('#000', '#fff'), 0.05);
    }

    public function testSameColourIsOne(): void
    {
        self::assertEqualsWithDelta(1.0, (float) ColorContrast::ratio('#345678', '#345678'), 0.001);
    }

    public function testOklchMidContrastIsReasonable(): void
    {
        // Near-black ink on near-white paper → should comfortably pass AA (>= 4.5).
        $ratio = ColorContrast::ratio('oklch(22% 0.02 250)', 'oklch(98% 0.01 250)');
        self::assertNotNull($ratio);
        self::assertGreaterThan(7.0, $ratio);
    }

    public function testUncomputableValuesReturnNull(): void
    {
        self::assertNull(ColorContrast::relativeLuminance('color-mix(in oklch, #fff, #000 40%)'));
        self::assertNull(ColorContrast::relativeLuminance('var(--x)'));
        self::assertNull(ColorContrast::ratio('#fff', 'not-a-color'));
    }
}
