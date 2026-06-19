<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Theme;

use NeNeRecords\Theme\PreviewThemeInput;
use NeNeRecords\Theme\PreviewThemeUseCase;
use PHPUnit\Framework\TestCase;

final class PreviewThemeUseCaseTest extends TestCase
{
    public function testValidManifestReportsComputableContrast(): void
    {
        $output = (new PreviewThemeUseCase())->execute(
            new PreviewThemeInput(ThemeManifestFixture::valid()),
        );

        self::assertTrue($output->valid);
        self::assertSame([], $output->errors);
        self::assertArrayHasKey('color-surface', $output->applied['light']);
        // Every pair present for light, and at least one computed.
        self::assertNotEmpty($output->contrast['light']);
        $computable = array_filter(
            $output->contrast['light'],
            static fn (array $row) => ($row['computable'] ?? false) === true,
        );
        self::assertNotEmpty($computable);
    }

    public function testHighContrastPairPassesAa(): void
    {
        $manifest = ThemeManifestFixture::valid();
        $manifest['tokens']['light']['color-text-primary'] = 'oklch(20% 0.02 250)';
        $manifest['tokens']['light']['color-surface'] = 'oklch(98% 0.01 250)';

        $output = (new PreviewThemeUseCase())->execute(new PreviewThemeInput($manifest));

        $row = $this->pair($output->contrast['light'], 'text-primary/surface');
        self::assertTrue($row['computable']);
        self::assertTrue($row['aa']);
        self::assertGreaterThan(7.0, $row['ratio']);
    }

    public function testUnsafeValueIsDroppedAndReportedInvalid(): void
    {
        $manifest = ThemeManifestFixture::valid();
        $manifest['tokens']['light']['color-accent'] = 'red; } body{}';

        $output = (new PreviewThemeUseCase())->execute(new PreviewThemeInput($manifest));

        self::assertFalse($output->valid); // validator flags the unsafe value
        $droppedTokens = array_map(static fn (array $d) => $d['token'], $output->dropped);
        self::assertContains('color-accent', $droppedTokens);
    }

    public function testColorMixIsUncomputableWithWarning(): void
    {
        $manifest = ThemeManifestFixture::valid();
        $manifest['tokens']['light']['color-surface'] = 'color-mix(in oklch, #fff, #000 10%)';

        $output = (new PreviewThemeUseCase())->execute(new PreviewThemeInput($manifest));

        $row = $this->pair($output->contrast['light'], 'text-primary/surface');
        self::assertFalse($row['computable']);
        self::assertNotEmpty($output->warnings);
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return array<string, mixed>
     */
    private function pair(array $rows, string $label): array
    {
        foreach ($rows as $row) {
            if (($row['pair'] ?? null) === $label) {
                return $row;
            }
        }
        self::fail("contrast pair {$label} not found");
    }
}
