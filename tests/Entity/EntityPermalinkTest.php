<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Entity;

use NeNeRecords\Entity\EntityPermalink;
use PHPUnit\Framework\TestCase;

final class EntityPermalinkTest extends TestCase
{
    public function testNormalizeProducesCanonicalForm(): void
    {
        // lowercase + single leading slash + collapse "//" + strip trailing slash
        self::assertSame('/company/about/team', EntityPermalink::normalize('Company/About//Team/'));
        self::assertSame('/company/about/team', EntityPermalink::normalize('/company/about/team'));
        self::assertSame('/about', EntityPermalink::normalize('  about  '));
        self::assertSame('/about', EntityPermalink::normalize('///about///'));
        // empty / slash-only collapse to "" = no custom permalink
        self::assertSame('', EntityPermalink::normalize(''));
        self::assertSame('', EntityPermalink::normalize('   '));
        self::assertSame('', EntityPermalink::normalize('/'));
    }

    public function testValidateAcceptsKebabPaths(): void
    {
        self::assertNull(EntityPermalink::validate('/company/about/team'));
        self::assertNull(EntityPermalink::validate('/p404'));
        self::assertNull(EntityPermalink::validate('/our-team/2026'));
    }

    public function testValidateRejectsBadFormat(): void
    {
        self::assertSame(EntityPermalink::ERROR_INVALID, EntityPermalink::validate(''));
        self::assertSame(EntityPermalink::ERROR_INVALID, EntityPermalink::validate('/has space'));
        self::assertSame(EntityPermalink::ERROR_INVALID, EntityPermalink::validate('/trailing/'));
        self::assertSame(EntityPermalink::ERROR_INVALID, EntityPermalink::validate('no-leading-slash'));
        self::assertSame(EntityPermalink::ERROR_INVALID, EntityPermalink::validate('/under_score'));
    }

    public function testValidateRejectsReservedPrefixes(): void
    {
        $reserved = [
            '/api',
            '/api/x',
            '/admin',
            '/superadmin/y',
            '/health',
            '/view/z',
            '/feed',
            '/feeds/rss', // "feed*"
            '/media/x',
            '/assets/x',
        ];

        foreach ($reserved as $path) {
            self::assertSame(EntityPermalink::ERROR_RESERVED, EntityPermalink::validate($path), $path);
        }
    }

    public function testValidateRejectsTooLong(): void
    {
        $long = '/' . str_repeat('a', EntityPermalink::MAX_LENGTH);
        self::assertSame(EntityPermalink::ERROR_TOO_LONG, EntityPermalink::validate($long));
    }

    public function testMessageForErrorCoversEveryCode(): void
    {
        self::assertNotSame('', EntityPermalink::messageForError(EntityPermalink::ERROR_INVALID));
        self::assertNotSame('', EntityPermalink::messageForError(EntityPermalink::ERROR_RESERVED));
        self::assertNotSame('', EntityPermalink::messageForError(EntityPermalink::ERROR_TOO_LONG));
    }
}
