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

    public function testExampleManifestIsRealisticNotPlaceholder(): void
    {
        $tokens = ThemeAuthoringGuide::build()['exampleManifest']['tokens'];

        // Light mode must read (text != surface), and modes must differ — guards
        // against the misleading "all one colour / inverted modes" example (#442).
        self::assertNotSame($tokens['light']['color-surface'], $tokens['light']['color-text-primary']);
        self::assertNotSame($tokens['light']['color-surface'], $tokens['dark']['color-surface']);
        self::assertNotSame($tokens['light']['color-accent'], $tokens['light']['color-on-accent']);
    }

    public function testTokenAndFlagDocsCoverTheWholeContractNoDrift(): void
    {
        $guide = ThemeAuthoringGuide::build();
        $contract = ThemeManifestValidator::contract();

        // If a token/flag is added to the validator, these force a doc update.
        self::assertEqualsCanonicalizing(
            $contract['requiredTokens'],
            array_keys($guide['tokenDocs']),
        );
        self::assertEqualsCanonicalizing(
            array_keys($contract['flags']),
            array_keys($guide['flagDocs']),
        );
    }

    public function testOptionalFieldsDocumentFontsAndAssets(): void
    {
        $optional = ThemeAuthoringGuide::build()['optionalFields'];

        self::assertSame(['fontsource', 'system'], $optional['fonts']['sources']);
        self::assertArrayHasKey('preview', $optional['assets']['example']);
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
