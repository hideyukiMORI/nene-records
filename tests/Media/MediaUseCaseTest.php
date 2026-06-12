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
