<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\WxrImport;

use NeNeRecords\Media\UploadMediaInput;
use NeNeRecords\Media\UploadMediaOutput;
use NeNeRecords\Media\UploadMediaUseCaseInterface;

/** Stores nothing; returns a deterministic media URL derived from the file name. */
final class FakeUploadMediaUseCase implements UploadMediaUseCaseInterface
{
    private int $nextId = 1;

    public function execute(UploadMediaInput $input): UploadMediaOutput
    {
        $id = $this->nextId++;

        return new UploadMediaOutput(
            id: $id,
            url: '/media/imported/' . $input->originalName,
            originalName: $input->originalName,
            mimeType: $input->mimeType,
            size: $input->size,
        );
    }
}
