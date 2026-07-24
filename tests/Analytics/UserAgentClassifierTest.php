<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Analytics;

use NeNeRecords\Analytics\UserAgentClassifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UserAgentClassifierTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function agents(): iterable
    {
        yield 'googlebot' => ['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'bot', true];
        yield 'gptbot' => ['Mozilla/5.0 (compatible; GPTBot/1.0)', 'bot', true];
        yield 'curl' => ['curl/8.1.2', 'bot', true];
        yield 'iphone' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)', 'mobile', false];
        yield 'android' => ['Mozilla/5.0 (Linux; Android 14; Pixel 8) Mobile', 'mobile', false];
        yield 'ipad' => ['Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X)', 'mobile', false];
        yield 'windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'desktop', false];
        yield 'mac' => ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', 'desktop', false];
        yield 'empty' => ['', 'other', false];
        yield 'unknown' => ['SomeRandomAgent/1.0', 'other', false];
    }

    #[DataProvider('agents')]
    public function testClassify(string $ua, string $expectedType, bool $expectedBot): void
    {
        $result = UserAgentClassifier::classify($ua);

        self::assertSame($expectedType, $result['type']);
        self::assertSame($expectedBot, $result['isBot']);
    }
}
