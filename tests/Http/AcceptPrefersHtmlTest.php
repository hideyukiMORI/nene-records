<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Http;

use NeNeRecords\Http\AcceptPrefersHtml;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AcceptPrefersHtmlTest extends TestCase
{
    #[Test]
    public function wants_html_for_browser_and_catch_all_and_missing_accept(): void
    {
        self::assertTrue(AcceptPrefersHtml::check('text/html,application/xhtml+xml,*/*;q=0.8'));
        self::assertTrue(AcceptPrefersHtml::check('*/*'));
        self::assertTrue(AcceptPrefersHtml::check(''));
        // Real unfurler shapes: catch-all with parameters.
        self::assertTrue(AcceptPrefersHtml::check('*/*;q=0.9'));
    }

    #[Test]
    public function keeps_json_for_an_explicit_non_html_accept(): void
    {
        self::assertFalse(AcceptPrefersHtml::check('application/json'));
        self::assertFalse(AcceptPrefersHtml::check('application/problem+json, application/json'));
        self::assertFalse(AcceptPrefersHtml::check('text/plain'));
    }
}
