<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\WxrImport;

use NeNeRecords\WxrImport\WxrDocument;
use NeNeRecords\WxrImport\WxrMediaFetchResult;
use NeNeRecords\WxrImport\WxrMediaImporter;
use NeNeRecords\WxrImport\WxrParser;
use PHPUnit\Framework\TestCase;

final class WxrMediaImporterTest extends TestCase
{
    private const ATTACHMENT_URL = 'https://old.example.com/wp-content/uploads/2024/01/image.jpg';

    private function document(): WxrDocument
    {
        $xml = file_get_contents(__DIR__ . '/fixtures/sample.wxr.xml');
        self::assertNotFalse($xml);

        return (new WxrParser())->parse($xml);
    }

    public function testImportsAttachmentsAndBuildsUrlMap(): void
    {
        $fetcher = new FakeWxrMediaFetcher([
            self::ATTACHMENT_URL => new WxrMediaFetchResult('binary-bytes', 'image/jpeg', 'image.jpg'),
        ]);
        $importer = new WxrMediaImporter($fetcher, new FakeUploadMediaUseCase());

        $result = $importer->importAttachments($this->document());

        self::assertSame(1, $result->imported);
        self::assertSame(0, $result->skipped);
        self::assertSame('/media/imported/image.jpg', $result->urlMap[self::ATTACHMENT_URL] ?? null);
    }

    public function testSkipsUnfetchableAttachments(): void
    {
        // Empty fetcher → every fetch returns null.
        $importer = new WxrMediaImporter(new FakeWxrMediaFetcher(), new FakeUploadMediaUseCase());

        $result = $importer->importAttachments($this->document());

        self::assertSame(0, $result->imported);
        self::assertSame(1, $result->skipped); // image.jpg has an attachment_url but is unreachable
        self::assertSame([], $result->urlMap);
    }
}
