<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Setting;

use NeNeRecords\Setting\SettingValueInvalidException;
use NeNeRecords\Setting\SettingValueValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SettingValueValidatorTest extends TestCase
{
    // ── normalize ────────────────────────────────────────────────────────────

    #[DataProvider('provideBoolNormalize')]
    public function testNormalizeBool(string $input, string $expected): void
    {
        self::assertSame($expected, SettingValueValidator::normalize('bool', $input));
    }

    /** @return iterable<string, array{string, string}> */
    public static function provideBoolNormalize(): iterable
    {
        yield '1 → true'           => ['1', 'true'];
        yield 'true → true'        => ['true', 'true'];
        yield 'TRUE → true'        => ['TRUE', 'true'];
        yield '  True  → true'     => ['  True  ', 'true'];
        yield '0 → false'          => ['0', 'false'];
        yield 'false → false'      => ['false', 'false'];
        yield 'FALSE → false'      => ['FALSE', 'false'];
        yield '  False  → false'   => ['  False  ', 'false'];
    }

    public function testNormalizeBoolWithInvalidValueThrows(): void
    {
        $this->expectException(SettingValueInvalidException::class);
        SettingValueValidator::normalize('bool', 'yes');
    }

    public function testNormalizeTextPassesThrough(): void
    {
        self::assertSame('hello', SettingValueValidator::normalize('text', 'hello'));
    }

    public function testNormalizeMarkdownPassesThrough(): void
    {
        self::assertSame('# Heading', SettingValueValidator::normalize('markdown', '# Heading'));
    }

    public function testNormalizeUrlPassesThrough(): void
    {
        self::assertSame('https://example.com', SettingValueValidator::normalize('url', 'https://example.com'));
    }

    public function testNormalizeUnsupportedTypeThrows(): void
    {
        $this->expectException(SettingValueInvalidException::class);
        SettingValueValidator::normalize('json', 'value');
    }

    // ── validate ─────────────────────────────────────────────────────────────

    public function testValidateBoolWithTrueDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        SettingValueValidator::validate('bool', 'true');
    }

    public function testValidateBoolWithFalseDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        SettingValueValidator::validate('bool', 'false');
    }

    public function testValidateBoolWithInvalidValueThrows(): void
    {
        $this->expectException(SettingValueInvalidException::class);
        SettingValueValidator::validate('bool', 'maybe');
    }

    public function testValidateUrlWithValidUrlDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        SettingValueValidator::validate('url', 'https://example.com/path?q=1');
    }

    public function testValidateUrlWithEmptyStringDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        SettingValueValidator::validate('url', '');
    }

    public function testValidateUrlWithInvalidUrlThrows(): void
    {
        $this->expectException(SettingValueInvalidException::class);
        SettingValueValidator::validate('url', 'not-a-url');
    }

    public function testValidateTextDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        SettingValueValidator::validate('text', 'anything goes');
    }

    public function testValidateMarkdownDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        SettingValueValidator::validate('markdown', '# Header\n\nContent');
    }

    public function testValidateUnsupportedTypeThrows(): void
    {
        $this->expectException(SettingValueInvalidException::class);
        SettingValueValidator::validate('xml', 'value');
    }
}
