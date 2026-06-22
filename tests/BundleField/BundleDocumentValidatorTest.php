<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\BundleField;

use Nene2\Validation\ValidationException;
use NeNeRecords\BundleField\BundleDocumentValidator;
use PHPUnit\Framework\TestCase;

final class BundleDocumentValidatorTest extends TestCase
{
    private BundleDocumentValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new BundleDocumentValidator();
    }

    public function testAcceptsValidBundle(): void
    {
        $this->validator->validate('{"html":"<h1>Hi</h1>","seoText":"# Hi\n\nWelcome."}');
        $this->addToAssertionCount(1);
    }

    public function testRejectsBundleWithoutSeoText(): void
    {
        // seoText is REQUIRED (dual-representation / no-cloaking contract).
        $this->expectException(ValidationException::class);
        $this->validator->validate('{"html":"<h1>Hi</h1>","seoText":"   "}');
    }

    public function testRejectsMissingSeoText(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validate('{"html":"<h1>Hi</h1>"}');
    }

    public function testRejectsNonObject(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validate('[1,2,3]');
    }

    public function testRejectsNonJson(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validate('not json');
    }

    public function testSeoTextOfExtractsOrEmpty(): void
    {
        self::assertSame('# Hi', BundleDocumentValidator::seoTextOf('{"html":"x","seoText":"# Hi"}'));
        self::assertSame('', BundleDocumentValidator::seoTextOf('legacy raw html'));
    }

    public function testAcceptsLargeBundleUnderNewCap(): void
    {
        // ~200KB html — exceeds the old TEXT/50KB ceiling, fits LONGTEXT (#491 WS3-S3b).
        $html = str_repeat('a', 200000);
        $this->validator->validate(json_encode(['html' => $html, 'seoText' => '# Big'], JSON_THROW_ON_ERROR));
        $this->addToAssertionCount(1);
    }

    public function testRejectsBundleOverHtmlCap(): void
    {
        $html = str_repeat('a', BundleDocumentValidator::MAX_HTML_LEN + 1);
        $this->expectException(ValidationException::class);
        $this->validator->validate(json_encode(['html' => $html, 'seoText' => '# x'], JSON_THROW_ON_ERROR));
    }
}
