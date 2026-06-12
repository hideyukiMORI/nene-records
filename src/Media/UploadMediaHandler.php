<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class UploadMediaHandler
{
    public function __construct(
        private UploadMediaUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uploadedFiles = $request->getUploadedFiles();
        $file = $uploadedFiles['file'] ?? null;

        if ($file === null) {
            throw new ValidationException([
                new ValidationError('file', 'A file is required.', 'required'),
            ]);
        }

        // PSR-7 UploadedFileInterface
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new ValidationException([
                new ValidationError('file', 'File upload failed.', 'upload_error'),
            ]);
        }

        $stream = $file->getStream();
        $tmpPath = $stream->getMetadata('uri');

        if (!is_string($tmpPath)) {
            throw new ValidationException([
                new ValidationError('file', 'Could not read uploaded file.', 'upload_error'),
            ]);
        }

        $originalName = $file->getClientFilename() ?? 'upload';
        $mimeType = $file->getClientMediaType() ?? 'application/octet-stream';
        $size = (int) $file->getSize();

        $output = $this->useCase->execute(new UploadMediaInput(
            tmpPath: $tmpPath,
            originalName: $originalName,
            mimeType: $mimeType,
            size: $size,
        ));

        return $this->response->create([
            'id' => $output->id,
            'url' => $output->url,
            'original_name' => $output->originalName,
            'mime_type' => $output->mimeType,
            'size' => $output->size,
            'width' => $output->width,
            'height' => $output->height,
            'alt_text' => $output->altText,
            'created_at' => $output->createdAt,
        ], 201);
    }
}
