<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PublicRecord;

use NeNeRecords\PublicRecord\PermalinkLabel;
use PHPUnit\Framework\TestCase;

final class PermalinkLabelTest extends TestCase
{
    public function testHumanizeTitleCasesKebabSegments(): void
    {
        self::assertSame('About Us', PermalinkLabel::humanize('about-us'));
        self::assertSame('Guides', PermalinkLabel::humanize('guides'));
    }

    public function testHumanizeFallsBackToRawForNonKebabInput(): void
    {
        self::assertSame('FAQ', PermalinkLabel::humanize('FAQ'));
    }

    public function testLastSegmentReturnsFinalPathComponent(): void
    {
        self::assertSame('about', PermalinkLabel::lastSegment('/company/about'));
        self::assertSame('team', PermalinkLabel::lastSegment('/company/about/team/'));
    }

    public function testLastSegmentIsNullForEmptyOrRootPaths(): void
    {
        self::assertNull(PermalinkLabel::lastSegment(null));
        self::assertNull(PermalinkLabel::lastSegment(''));
        self::assertNull(PermalinkLabel::lastSegment('/'));
    }
}
