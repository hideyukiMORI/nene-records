<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PublicRecord;

use NeNeRecords\PublicRecord\PublicDocumentTitle;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PublicDocumentTitleTest extends TestCase
{
    #[Test]
    public function appends_site_name_when_title_does_not_carry_it(): void
    {
        self::assertSame(
            '『文学論』序 — NeNe Records',
            PublicDocumentTitle::compose('『文学論』序', 'NeNe Records'),
        );
    }

    #[Test]
    public function skips_suffix_when_title_already_contains_site_name(): void
    {
        self::assertSame(
            'サービスと料金｜彩音インターナショナル株式会社',
            PublicDocumentTitle::compose(
                'サービスと料金｜彩音インターナショナル株式会社',
                '彩音インターナショナル株式会社',
            ),
        );
    }

    #[Test]
    public function falls_back_to_site_name_for_empty_title(): void
    {
        self::assertSame('NeNe Records', PublicDocumentTitle::compose('  ', 'NeNe Records'));
    }

    #[Test]
    public function keeps_bare_title_when_site_name_is_empty(): void
    {
        self::assertSame('ページ名', PublicDocumentTitle::compose('ページ名', ''));
    }
}
