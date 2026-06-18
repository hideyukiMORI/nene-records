<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Theme;

use Nene2\Validation\ValidationException;
use NeNeRecords\Theme\ThemeManifestValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ThemeManifestValidatorTest extends TestCase
{
    public function testValidManifestPasses(): void
    {
        ThemeManifestValidator::validate(ThemeManifestFixture::valid());
        $this->addToAssertionCount(1);
    }

    /** @param array<string, mixed> $manifest */
    #[DataProvider('provideInvalidManifests')]
    public function testInvalidManifestThrows(array $manifest): void
    {
        $this->expectException(ValidationException::class);
        ThemeManifestValidator::validate($manifest);
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function provideInvalidManifests(): iterable
    {
        yield 'bad id' => [ThemeManifestFixture::valid(['id' => 'Bad Id!'])];
        yield 'reserved id' => [ThemeManifestFixture::valid(['id' => 'aurora'])];
        yield 'empty name' => [ThemeManifestFixture::valid(['name' => ''])];
        yield 'bad version' => [ThemeManifestFixture::valid(['version' => 'v1'])];
        yield 'missing dark mode' => [ThemeManifestFixture::valid(['supportsModes' => ['light']])];
        yield 'unknown flag' => [ThemeManifestFixture::valid(['flags' => ['bogus' => 'x']])];
        yield 'bad flag value' => [ThemeManifestFixture::valid(['flags' => ['feedLayout' => 'spaceship']])];

        $missingToken = ThemeManifestFixture::valid();
        unset($missingToken['tokens']['light']['color-accent']);
        yield 'missing required token' => [$missingToken];

        $badKey = ThemeManifestFixture::valid();
        $badKey['tokens']['light']['Bad_Key'] = 'oklch(60% 0.1 250)';
        yield 'bad token key' => [$badKey];

        $selfhostFont = ThemeManifestFixture::valid(['fonts' => [['family' => 'X', 'role' => 'body', 'source' => 'selfhost']]]);
        yield 'selfhost font' => [$selfhostFont];
    }

    /** Unsafe token values that could break out of `--key: value;` / <style>. */
    #[DataProvider('provideUnsafeValues')]
    public function testUnsafeTokenValueRejected(string $value): void
    {
        $manifest = ThemeManifestFixture::valid();
        $manifest['tokens']['light']['color-accent'] = $value;

        $this->expectException(ValidationException::class);
        ThemeManifestValidator::validate($manifest);
    }

    /** @return iterable<string, array{string}> */
    public static function provideUnsafeValues(): iterable
    {
        yield 'semicolon breakout' => ['red; } body { display:none'];
        yield 'brace' => ['red}'];
        yield 'url()' => ['url(https://evil.test/x.png)'];
        yield '@import' => ['@import "https://evil.test"'];
        yield 'expression' => ['expression(alert(1))'];
        yield 'angle bracket' => ['</style><script>'];
        yield 'comment' => ['red /* x */'];
        yield 'empty' => [''];
    }

    #[DataProvider('provideSafeValues')]
    public function testSafeValuesAccepted(string $value): void
    {
        self::assertTrue(ThemeManifestValidator::isSafeCssValue($value));
    }

    /** @return iterable<string, array{string}> */
    public static function provideSafeValues(): iterable
    {
        yield 'oklch' => ['oklch(97% 0.012 75)'];
        yield 'hex' => ['#1a2b3c'];
        yield 'color-mix' => ['color-mix(in oklch, #fff, #000 12%)'];
        yield 'clamp' => ['clamp(1rem, 0.5rem + 2vw, 2rem)'];
        yield 'keyword' => ['dark'];
    }
}
