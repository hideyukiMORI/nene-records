<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Media;

use NeNeRecords\Media\DeleteMediaInput;
use NeNeRecords\Media\DeleteMediaUseCase;
use NeNeRecords\Media\LocalStorage;
use NeNeRecords\Media\Media;
use NeNeRecords\Media\MediaInvalidTypeException;
use NeNeRecords\Media\MediaNotFoundException;
use NeNeRecords\Media\MediaTooLargeException;
use NeNeRecords\Media\UploadMediaInput;
use NeNeRecords\Media\UploadMediaUseCase;
use PHPUnit\Framework\TestCase;

final class MediaUseCaseTest extends TestCase
{
    public function testUploadValidImageSucceeds(): void
    {
        $storageRoot = sys_get_temp_dir() . '/nene_media_test_' . uniqid('', true);
        mkdir($storageRoot, 0755, true);

        $tmpFile = tempnam(sys_get_temp_dir(), 'nene_upload_');
        file_put_contents($tmpFile, 'fake image content');

        $mediaRepo = new InMemoryMediaRepository();
        $useCase = new UploadMediaUseCase($mediaRepo, new LocalStorage($storageRoot));

        $input = new UploadMediaInput(
            tmpPath: $tmpFile,
            originalName: 'photo.jpg',
            mimeType: 'image/jpeg',
            size: 1024,
        );

        $output = $useCase->execute($input);

        self::assertSame(1, $output->id);
        self::assertSame('photo.jpg', $output->originalName);
        self::assertSame('image/jpeg', $output->mimeType);
        self::assertSame(1024, $output->size);
        self::assertStringStartsWith('/media/', $output->url);

        // Clean up
        unlink($tmpFile);
    }

    public function testUploadCapturesImageDimensionsAndStorageKey(): void
    {
        $storageRoot = sys_get_temp_dir() . '/nene_media_test_' . uniqid('', true);
        mkdir($storageRoot, 0755, true);

        // 1x1 PNG.
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true,
        );
        self::assertIsString($png);
        $tmpFile = tempnam(sys_get_temp_dir(), 'nene_upload_');
        file_put_contents($tmpFile, $png);

        $mediaRepo = new InMemoryMediaRepository();
        $useCase = new UploadMediaUseCase($mediaRepo, new LocalStorage($storageRoot));

        $output = $useCase->execute(new UploadMediaInput(
            tmpPath: $tmpFile,
            originalName: 'pixel.png',
            mimeType: 'image/png',
            size: strlen($png),
        ));

        self::assertSame(1, $output->width);
        self::assertSame(1, $output->height);

        $saved = $mediaRepo->findById($output->id);
        self::assertNotNull($saved);
        self::assertSame(1, $saved->width);
        self::assertSame(1, $saved->height);
        self::assertNotSame('', $saved->storageKey);
        self::assertStringEndsWith('.png', $saved->storageKey);

        unlink($tmpFile);
    }

    public function testUploadThrowsMediaInvalidTypeExceptionForUnsupportedMimeType(): void
    {
        $storageRoot = sys_get_temp_dir() . '/nene_media_test_' . uniqid('', true);
        $mediaRepo = new InMemoryMediaRepository();
        $useCase = new UploadMediaUseCase($mediaRepo, new LocalStorage($storageRoot));

        $input = new UploadMediaInput(
            tmpPath: '/tmp/irrelevant',
            originalName: 'malware.exe',
            mimeType: 'application/x-msdownload',
            size: 512,
        );

        $this->expectException(MediaInvalidTypeException::class);

        $useCase->execute($input);
    }

    public function testUploadThrowsMediaTooLargeExceptionWhenFileSizeExceedsTenMib(): void
    {
        $storageRoot = sys_get_temp_dir() . '/nene_media_test_' . uniqid('', true);
        $mediaRepo = new InMemoryMediaRepository();
        $useCase = new UploadMediaUseCase($mediaRepo, new LocalStorage($storageRoot));

        $tenMibPlusOne = 10 * 1024 * 1024 + 1;

        $input = new UploadMediaInput(
            tmpPath: '/tmp/irrelevant',
            originalName: 'big.jpg',
            mimeType: 'image/jpeg',
            size: $tenMibPlusOne,
        );

        $this->expectException(MediaTooLargeException::class);

        $useCase->execute($input);
    }

    public function testUploadSvgIsSanitizedBeforeStorage(): void
    {
        $storageRoot = sys_get_temp_dir() . '/nene_media_test_' . uniqid('', true);
        mkdir($storageRoot, 0755, true);

        $tmpFile = tempnam(sys_get_temp_dir(), 'nene_upload_');
        file_put_contents(
            $tmpFile,
            '<svg xmlns="http://www.w3.org/2000/svg"><script>fetch("/steal")</script><rect width="1" height="1" onload="x()"/></svg>',
        );

        $mediaRepo = new InMemoryMediaRepository();
        $useCase = new UploadMediaUseCase($mediaRepo, new LocalStorage($storageRoot));

        $output = $useCase->execute(new UploadMediaInput(
            tmpPath: $tmpFile,
            originalName: 'logo.svg',
            mimeType: 'image/svg+xml',
            size: filesize($tmpFile) ?: 0,
        ));

        self::assertSame('image/svg+xml', $output->mimeType);

        $saved = $mediaRepo->findById($output->id);
        self::assertNotNull($saved);
        self::assertStringEndsWith('.svg', $saved->storageKey);

        $stored = (string) file_get_contents($storageRoot . '/' . $saved->storageKey);
        self::assertStringNotContainsStringIgnoringCase('script', $stored);
        self::assertStringNotContainsString('fetch(', $stored);
        self::assertStringNotContainsStringIgnoringCase('onload', $stored);
        self::assertStringContainsString('<rect', $stored);

        unlink($tmpFile);
    }

    public function testUploadSvgSpoofedAsPngIsDetectedAndSanitized(): void
    {
        $storageRoot = sys_get_temp_dir() . '/nene_media_test_' . uniqid('', true);
        mkdir($storageRoot, 0755, true);

        $tmpFile = tempnam(sys_get_temp_dir(), 'nene_upload_');
        file_put_contents($tmpFile, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        $mediaRepo = new InMemoryMediaRepository();
        $useCase = new UploadMediaUseCase($mediaRepo, new LocalStorage($storageRoot));

        // Declared as PNG to try to dodge the SVG path — content sniff must catch it.
        $output = $useCase->execute(new UploadMediaInput(
            tmpPath: $tmpFile,
            originalName: 'evil.png',
            mimeType: 'image/png',
            size: filesize($tmpFile) ?: 0,
        ));

        self::assertSame('image/svg+xml', $output->mimeType);

        $saved = $mediaRepo->findById($output->id);
        self::assertNotNull($saved);
        self::assertStringEndsWith('.svg', $saved->storageKey);

        $stored = (string) file_get_contents($storageRoot . '/' . $saved->storageKey);
        self::assertStringNotContainsString('alert(1)', $stored);

        unlink($tmpFile);
    }

    public function testUploadOversizeSvgIsRejected(): void
    {
        $storageRoot = sys_get_temp_dir() . '/nene_media_test_' . uniqid('', true);
        mkdir($storageRoot, 0755, true);

        $tmpFile = tempnam(sys_get_temp_dir(), 'nene_upload_');
        $padding = str_repeat('<rect width="1" height="1"/>', 3000); // > 64 KiB
        file_put_contents($tmpFile, '<svg xmlns="http://www.w3.org/2000/svg">' . $padding . '</svg>');

        $mediaRepo = new InMemoryMediaRepository();
        $useCase = new UploadMediaUseCase($mediaRepo, new LocalStorage($storageRoot));

        $this->expectException(MediaTooLargeException::class);

        try {
            $useCase->execute(new UploadMediaInput(
                tmpPath: $tmpFile,
                originalName: 'big.svg',
                mimeType: 'image/svg+xml',
                size: filesize($tmpFile) ?: 0,
            ));
        } finally {
            unlink($tmpFile);
        }
    }

    public function testUploadInvalidSvgIsRejected(): void
    {
        $storageRoot = sys_get_temp_dir() . '/nene_media_test_' . uniqid('', true);
        mkdir($storageRoot, 0755, true);

        $tmpFile = tempnam(sys_get_temp_dir(), 'nene_upload_');
        file_put_contents($tmpFile, '<svg xmlns="http://www.w3.org/2000/svg"><rect'); // truncated / unparseable

        $mediaRepo = new InMemoryMediaRepository();
        $useCase = new UploadMediaUseCase($mediaRepo, new LocalStorage($storageRoot));

        $this->expectException(MediaInvalidTypeException::class);

        try {
            $useCase->execute(new UploadMediaInput(
                tmpPath: $tmpFile,
                originalName: 'broken.svg',
                mimeType: 'image/svg+xml',
                size: filesize($tmpFile) ?: 0,
            ));
        } finally {
            unlink($tmpFile);
        }
    }

    public function testDeleteRemovesMediaRecord(): void
    {
        $seeded = new Media(
            id: 1,
            originalName: 'photo.jpg',
            storedName: 'abc123.jpg',
            mimeType: 'image/jpeg',
            size: 1024,
            url: '/media/2024/01/abc123.jpg',
            createdAt: '2024-01-01 00:00:00',
        );

        $storageRoot = sys_get_temp_dir() . '/nene_no_file_' . uniqid('', true);

        $mediaRepo = new InMemoryMediaRepository([$seeded]);
        $useCase = new DeleteMediaUseCase($mediaRepo, new LocalStorage($storageRoot));

        $useCase->execute(new DeleteMediaInput(id: 1));

        self::assertNull($mediaRepo->findById(1));
    }

    public function testDeleteThrowsMediaNotFoundExceptionWhenMediaDoesNotExist(): void
    {
        $mediaRepo = new InMemoryMediaRepository();
        $useCase = new DeleteMediaUseCase($mediaRepo, new LocalStorage(sys_get_temp_dir()));

        $this->expectException(MediaNotFoundException::class);

        $useCase->execute(new DeleteMediaInput(id: 999));
    }
}
