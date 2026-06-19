<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Theme;

use NeNeRecords\Theme\ThemeAuthoringGuide;
use NeNeRecords\Theme\ThemeManifestValidator;
use PHPUnit\Framework\TestCase;

final class ThemeAuthoringGuideTest extends TestCase
{
    public function testGuideExposesTheValidatorContract(): void
    {
        $guide = ThemeAuthoringGuide::build();

        self::assertSame(ThemeManifestValidator::contract(), $guide['contract']);
        self::assertContains('color-accent', $guide['contract']['requiredTokens']);
        self::assertArrayHasKey('feedLayout', $guide['contract']['flags']);
    }

    public function testExampleManifestPassesValidation(): void
    {
        $guide = ThemeAuthoringGuide::build();

        // The advertised example must be something createTheme would accept,
        // otherwise the guide teaches ClaudeDesign to fail (#440).
        ThemeManifestValidator::validate($guide['exampleManifest']);
        $this->addToAssertionCount(1);
    }

    public function testGuideListsTheThemeToolsAndIsActionable(): void
    {
        $guide = ThemeAuthoringGuide::build();

        self::assertArrayHasKey('createTheme', $guide['relatedTools']);
        self::assertArrayHasKey('updateTheme', $guide['relatedTools']);
        self::assertNotEmpty($guide['recipes']);
        self::assertNotEmpty($guide['commonMistakes']);
        self::assertNotSame('', $guide['summary']);
    }
}
