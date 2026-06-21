<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

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

    /** SVG is sanitised but still capped hard — it should only ever be icons/logos. */
    private const SVG_MAX_SIZE_BYTES = 64 * 1024; // 64 KiB

    /** Bytes read from the head to detect SVG smuggled under a spoofed MIME. */
    private const SVG_SNIFF_BYTES = 8192;

    public function __construct(
        private MediaRepositoryInterface $mediaRepository,
        private StorageInterface $storage,
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

        // SVG path: detect by declared MIME *or* by sniffing the bytes, so a
        // spoofed Content-Type (e.g. image/png wrapping SVG) cannot bypass
        // sanitisation. SVG is the only upload type that can carry executable
        // content, so it is hard-capped, deep-sanitised, and stored as
        // image/svg+xml regardless of the declared type.
        $head = (string) @file_get_contents($input->tmpPath, false, null, 0, self::SVG_SNIFF_BYTES);
        if ($input->mimeType === 'image/svg+xml' || SvgSanitizer::looksLikeSvg($head)) {
            return $this->storeSvg($input);
        }

        $ext = $this->extensionForMimeType($input->mimeType);
        $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
        $key = date('Y') . '/' . date('m') . '/' . $storedName;

        // Read pixel dimensions from the header only (no full decode) before the
        // temp file is moved into storage. Returns null for non-image uploads.
        [$width, $height] = $this->imageDimensions($input->tmpPath);

        $this->storage->writeFromUpload($key, $input->tmpPath);

        $url = $this->storage->publicUrl($key);
        $now = date('Y-m-d H:i:s');

        $media = new Media(
            id: null,
            originalName: $input->originalName,
            storedName: $storedName,
            mimeType: $input->mimeType,
            size: $input->size,
            url: $url,
            createdAt: $now,
            storageKey: $key,
            width: $width,
            height: $height,
        );

        $id = $this->mediaRepository->save($media);

        return new UploadMediaOutput(
            id: $id,
            url: $url,
            originalName: $input->originalName,
            mimeType: $input->mimeType,
            size: $input->size,
            createdAt: $now,
            width: $width,
            height: $height,
        );
    }

    /**
     * Hard-cap, deep-sanitise and store an SVG upload. Always persists as
     * image/svg+xml with the sanitised bytes — never the raw upload.
     */
    private function storeSvg(UploadMediaInput $input): UploadMediaOutput
    {
        $actualSize = @filesize($input->tmpPath);
        $actualSize = $actualSize === false ? $input->size : $actualSize;

        if ($actualSize > self::SVG_MAX_SIZE_BYTES) {
            throw new MediaTooLargeException($actualSize, self::SVG_MAX_SIZE_BYTES);
        }

        $raw = (string) @file_get_contents($input->tmpPath);
        // Throws MediaInvalidTypeException on unparseable / non-SVG content.
        $clean = (new SvgSanitizer())->sanitize($raw);
        $size = strlen($clean);

        $storedName = bin2hex(random_bytes(16)) . '.svg';
        $key = date('Y') . '/' . date('m') . '/' . $storedName;

        $this->storage->write($key, $clean);

        $url = $this->storage->publicUrl($key);
        $now = date('Y-m-d H:i:s');

        $media = new Media(
            id: null,
            originalName: $input->originalName,
            storedName: $storedName,
            mimeType: 'image/svg+xml',
            size: $size,
            url: $url,
            createdAt: $now,
            storageKey: $key,
            width: null,
            height: null,
        );

        $id = $this->mediaRepository->save($media);

        return new UploadMediaOutput(
            id: $id,
            url: $url,
            originalName: $input->originalName,
            mimeType: 'image/svg+xml',
            size: $size,
            createdAt: $now,
            width: null,
            height: null,
        );
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    private function imageDimensions(string $path): array
    {
        $info = @getimagesize($path);

        if ($info === false) {
            return [null, null];
        }

        return [$info[0], $info[1]];
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
