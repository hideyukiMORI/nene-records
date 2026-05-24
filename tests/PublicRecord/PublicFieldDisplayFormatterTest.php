<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PublicRecord;

use NeNeRecords\PublicRecord\PublicFieldDisplayFormatter;
use PHPUnit\Framework\TestCase;

final class PublicFieldDisplayFormatterTest extends TestCase
{
    public function testFormatDateTimeConvertsToUtc(): void
    {
        // JST (+09:00) → UTC 変換で 9 時間引かれることを確認
        $result = PublicFieldDisplayFormatter::format('datetime', '2026-05-24T09:00:00+09:00');
        self::assertSame('2026-05-24 00:00:00 UTC', $result);
    }

    public function testFormatDateTimeAlreadyUtc(): void
    {
        $result = PublicFieldDisplayFormatter::format('datetime', '2026-05-24T12:30:00+00:00');
        self::assertSame('2026-05-24 12:30:00 UTC', $result);
    }

    public function testFormatDateTimeNegativeOffset(): void
    {
        // EST (-05:00) → UTC 変換で 5 時間足されることを確認
        $result = PublicFieldDisplayFormatter::format('datetime', '2026-05-24T07:00:00-05:00');
        self::assertSame('2026-05-24 12:00:00 UTC', $result);
    }

    public function testFormatDateTimeFallbackFormatWithoutTimezone(): void
    {
        // タイムゾーン情報なし → そのまま返す（パース失敗扱い）
        $result = PublicFieldDisplayFormatter::format('datetime', '2026-05-24 12:30:00');
        self::assertSame('2026-05-24 12:30:00 UTC', $result);
    }

    public function testFormatDateTimeEmpty(): void
    {
        $result = PublicFieldDisplayFormatter::format('datetime', '');
        self::assertSame('—', $result);
    }

    public function testFormatDateTimeNull(): void
    {
        $result = PublicFieldDisplayFormatter::format('datetime', null);
        self::assertSame('—', $result);
    }

    public function testFormatBool(): void
    {
        self::assertSame('Yes', PublicFieldDisplayFormatter::format('bool', 'true'));
        self::assertSame('No', PublicFieldDisplayFormatter::format('bool', 'false'));
        self::assertSame('Yes', PublicFieldDisplayFormatter::format('bool', '1'));
        self::assertSame('No', PublicFieldDisplayFormatter::format('bool', '0'));
    }

    public function testFormatInt(): void
    {
        self::assertSame('42', PublicFieldDisplayFormatter::format('int', 42));
    }

    public function testFormatTextEmpty(): void
    {
        self::assertSame('—', PublicFieldDisplayFormatter::format('text', '   '));
    }

    public function testFormatText(): void
    {
        self::assertSame('hello', PublicFieldDisplayFormatter::format('text', 'hello'));
    }
}
