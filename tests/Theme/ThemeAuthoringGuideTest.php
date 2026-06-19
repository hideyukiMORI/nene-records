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

    public function testRenderModelDescribesTheEngineContract(): void
    {
        $rm = ThemeAuthoringGuide::build()['renderModel'];

        self::assertNotSame('', $rm['premise']);
        self::assertStringContainsString('public-site.css', $rm['premise']);
        self::assertStringContainsString('AA', $rm['contrastTarget']);
        self::assertSame(ThemeManifestValidator::contract()['requiredTokens'], $rm['requiredTokens']);
    }

    public function testFlagAttributesMatchTheFrontendDefs(): void
    {
        $attrs = ThemeAuthoringGuide::build()['renderModel']['flagAttributes'];
        $contract = ThemeManifestValidator::contract();

        // Same flags as the contract, and each attribute string must appear in
        // the frontend FLAG_DEFS source — so a rename there fails this test.
        self::assertEqualsCanonicalizing(array_keys($contract['flags']), array_keys($attrs));
        $frontend = self::readFrontend('src/shared/lib/theme-customization.ts');
        foreach ($attrs as $flag => $attr) {
            self::assertStringContainsString("attr: '{$attr}'", $frontend, "flag {$flag}");
        }
    }

    public function testOptionalTokensCoverEveryEngineVarNoDrift(): void
    {
        $rm = ThemeAuthoringGuide::build()['renderModel'];
        $documented = array_merge($rm['requiredTokens'], array_keys($rm['optionalTokens']));

        // Every var(--x) the base engine reads must be a token an author can set.
        $css = self::readFrontend('src/pages/consumer/public-site.css');
        preg_match_all('/var\(--([a-z0-9-]+)\)/', $css, $matches);
        $engineVars = array_values(array_unique($matches[1]));
        self::assertNotEmpty($engineVars);

        $missing = array_diff($engineVars, $documented);
        self::assertSame([], array_values($missing), 'undocumented engine vars: ' . implode(', ', $missing));
    }

    private static function readFrontend(string $relative): string
    {
        $path = dirname(__DIR__, 2) . '/frontend/' . $relative;
        self::assertFileExists($path);

        return (string) file_get_contents($path);
    }
}
