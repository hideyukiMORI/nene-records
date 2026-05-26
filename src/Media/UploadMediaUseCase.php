<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

use RuntimeException;

final readonly class UploadMediaUseCase implements UploadMediaUseCaseInterface
{
    /** @var list<string> */
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'application/pdf',
    ];

    private const MAX_SIZE_BYTES = 10 * 1024 * 1024; // 10 MiB

    public function __construct(
        private MediaRepositoryInterface $mediaRepository,
        private string $storageRoot,
    ) {
    }

    public function execute(UploadMediaInput $input): UploadMediaOutput
    {
        if (!in_array($input->mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new MediaInvalidTypeException($input->mimeType);
        }

        if ($input->size > self::MAX_SIZE_BYTES) {
            throw new MediaTooLargeException($input->size, self::MAX_SIZE_BYTES);
        }

        $year = date('Y');
        $month = date('m');
        $ext = $this->extensionForMimeType($input->mimeType);
        $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
        $relativePath = $year . '/' . $month . '/' . $storedName;
        $absoluteDir = $this->storageRoot . '/' . $year . '/' . $month;

        if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0755, true) && !is_dir($absoluteDir)) {
            throw new RuntimeException('Failed to create media directory: ' . $absoluteDir);
        }

        $dest = $this->storageRoot . '/' . $relativePath;

        if (!move_uploaded_file($input->tmpPath, $dest)) {
            // Fallback for test environments where move_uploaded_file is not available
            if (!copy($input->tmpPath, $dest)) {
                throw new RuntimeException('Failed to move uploaded file to: ' . $dest);
            }
        }

        $url = '/media/' . $relativePath;
        $now = date('Y-m-d H:i:s');

        $media = new Media(
            id: null,
            originalName: $input->originalName,
            storedName: $storedName,
            mimeType: $input->mimeType,
            size: $input->size,
            url: $url,
            createdAt: $now,
        );

        $id = $this->mediaRepository->save($media);

        return new UploadMediaOutput(
            id: $id,
            url: $url,
            originalName: $input->originalName,
            mimeType: $input->mimeType,
            size: $input->size,
        );
    }

    private function extensionForMimeType(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'application/pdf' => 'pdf',
            default => 'bin',
        };
    }
}
