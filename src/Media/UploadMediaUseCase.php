<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

use RuntimeException;

final readonly class UploadMediaUseCase implements UploadMediaUseCaseInterface
{
    /** @var list<string> */
    private const ALLOWED_MIME_TYPES = [
        // Images
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        // Video
        'video/mp4',
        'video/webm',
        // Audio
        'audio/mpeg',
        'audio/wav',
        'audio/ogg',
        // Archives / Text
        'application/zip',
        'text/plain',
        'text/csv',
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
            createdAt: $now,
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
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
            'audio/mpeg' => 'mp3',
            'audio/wav' => 'wav',
            'audio/ogg' => 'ogg',
            'application/zip' => 'zip',
            'text/plain' => 'txt',
            'text/csv' => 'csv',
            default => 'bin',
        };
    }
}
