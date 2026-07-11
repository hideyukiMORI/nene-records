<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Http;

use NeNeRecords\Http\EmbedAllowlist;
use PHPUnit\Framework\TestCase;

final class EmbedAllowlistTest extends TestCase
{
    /** @param list<string> $origins */
    private static function fromOrigins(array $origins): EmbedAllowlist
    {
        return EmbedAllowlist::fromSettings(['embed_allowlist' => (string) json_encode($origins)]);
    }

    public function testEmptyWhenSettingAbsentOrBlank(): void
    {
        self::assertTrue(EmbedAllowlist::fromSettings([])->isEmpty());
        self::assertTrue(EmbedAllowlist::fromSettings(['embed_allowlist' => ''])->isEmpty());
        self::assertSame([], EmbedAllowlist::empty()->origins());
    }

    public function testAcceptsHttpsOriginsIncludingPort(): void
    {
        $list = self::fromOrigins(['https://contact.example.com', 'https://forms.example.co.jp:8443']);

        self::assertSame(
            ['https://contact.example.com', 'https://forms.example.co.jp:8443'],
            $list->origins(),
        );
        self::assertFalse($list->isEmpty());
    }

    public function testNormalisesCaseAndTrims(): void
    {
        $list = self::fromOrigins(['  HTTPS://Contact.Example.COM  ']);

        self::assertSame(['https://contact.example.com'], $list->origins());
    }

    public function testDropsInvalidOrigins(): void
    {
        $list = self::fromOrigins([
            'http://insecure.example.com',          // not https
            'https://*.example.com',                // wildcard
            'https://example.com/path',             // has a path
            'https://example.com?q=1',              // has a query
            'https://user:pw@example.com',          // userinfo
            'javascript:alert(1)',                  // scheme abuse
            'https://localhost',                    // no dot (not a domain)
            'not a url',
            'https://ok.example.com',               // the one good entry
        ]);

        self::assertSame(['https://ok.example.com'], $list->origins());
    }

    public function testDeduplicates(): void
    {
        $list = self::fromOrigins(['https://a.example.com', 'https://a.example.com', 'https://b.example.com']);

        self::assertSame(['https://a.example.com', 'https://b.example.com'], $list->origins());
    }

    public function testCapsTheNumberOfOrigins(): void
    {
        $many = [];
        for ($i = 0; $i < 25; $i++) {
            $many[] = "https://h{$i}.example.com";
        }

        self::assertLessThanOrEqual(10, count(self::fromOrigins($many)->origins()));
    }

    public function testIgnoresNonArrayOrNonStringEntries(): void
    {
        self::assertTrue(EmbedAllowlist::fromSettings(['embed_allowlist' => '"just a string"'])->isEmpty());
        self::assertTrue(EmbedAllowlist::fromSettings(['embed_allowlist' => 'not json'])->isEmpty());
        self::assertSame(
            ['https://ok.example.com'],
            EmbedAllowlist::fromSettings(['embed_allowlist' => (string) json_encode([42, null, 'https://ok.example.com'])])->origins(),
        );
    }
}
