<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Media;

use AsyncAws\S3\S3Client;
use NeNeRecords\Media\S3Storage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class S3StorageTest extends TestCase
{
    private const BASE_URL = 'https://cdn.example.com';

    public function testPublicUrlAndKeyFromUrlAreInverses(): void
    {
        $storage = $this->storage(new MockHttpClient());

        self::assertSame(self::BASE_URL . '/2026/06/a.png', $storage->publicUrl('2026/06/a.png'));
        self::assertSame('2026/06/a.png', $storage->keyFromUrl(self::BASE_URL . '/2026/06/a.png'));
        self::assertSame('2026/06/a.png', $storage->keyFromUrl($storage->publicUrl('2026/06/a.png')));
    }

    public function testPrefixIsAppliedToObjectKeysAndStrippedFromUrl(): void
    {
        $storage = $this->storage(new MockHttpClient(), prefix: 'media');

        self::assertSame(self::BASE_URL . '/media/2026/06/a.png', $storage->publicUrl('2026/06/a.png'));
        self::assertSame('2026/06/a.png', $storage->keyFromUrl(self::BASE_URL . '/media/2026/06/a.png'));
    }

    public function testSizeAndMimeTypeReadHeadObject(): void
    {
        $storage = $this->storage(new MockHttpClient(new MockResponse('', [
            'response_headers' => ['content-length' => '4242', 'content-type' => 'image/png'],
        ])));

        self::assertSame(4242, $storage->size('2026/06/a.png'));
    }

    public function testReadStreamReturnsObjectBody(): void
    {
        $storage = $this->storage(new MockHttpClient(new MockResponse('binary-payload', [
            'response_headers' => ['content-type' => 'image/png'],
        ])));

        $stream = $storage->readStream('2026/06/a.png');
        $contents = stream_get_contents($stream);

        self::assertSame('binary-payload', $contents);
    }

    public function testExistsReflectsHeadStatus(): void
    {
        $present = $this->storage(new MockHttpClient(new MockResponse('', ['http_code' => 200])));
        self::assertTrue($present->exists('2026/06/a.png'));

        $missing = $this->storage(new MockHttpClient(new MockResponse('', ['http_code' => 404])));
        self::assertFalse($missing->exists('2026/06/missing.png'));
    }

    public function testWriteFromUploadPutsObjectUnderPrefixedKey(): void
    {
        $captured = [];
        $client = new MockHttpClient(function (string $method, string $url) use (&$captured): MockResponse {
            $captured['method'] = $method;
            $captured['url'] = $url;

            return new MockResponse('', ['http_code' => 200]);
        });

        $tmp = tempnam(sys_get_temp_dir(), 'nene_s3_');
        self::assertIsString($tmp);
        file_put_contents($tmp, 'data');

        $this->storage($client, prefix: 'media')->writeFromUpload('2026/06/a.png', $tmp);
        unlink($tmp);

        self::assertSame('PUT', $captured['method']);
        self::assertStringContainsString('/media/2026/06/a.png', $captured['url']);
    }

    private function storage(MockHttpClient $http, string $prefix = ''): S3Storage
    {
        $client = new S3Client(
            ['region' => 'us-east-1', 'accessKeyId' => 'test', 'accessKeySecret' => 'test'],
            null,
            $http,
        );

        return new S3Storage($client, 'test-bucket', self::BASE_URL, $prefix);
    }
}
