<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

final readonly class UpdateMediaAltUseCase implements UpdateMediaAltUseCaseInterface
{
    public function __construct(
        private MediaRepositoryInterface $media,
    ) {
    }

    public function execute(UpdateMediaAltInput $input): Media
    {
        $media = $this->media->findById($input->id);

        if ($media === null) {
            throw new MediaNotFoundException($input->id);
        }

        $altText = $input->altText === null || trim($input->altText) === '' ? null : trim($input->altText);

        $this->media->updateAltText($input->id, $altText);

        return new Media(
            id: $media->id,
            originalName: $media->originalName,
            storedName: $media->storedName,
            mimeType: $media->mimeType,
            size: $media->size,
            url: $media->url,
            createdAt: $media->createdAt,
            storageKey: $media->storageKey,
            width: $media->width,
            height: $media->height,
            altText: $altText,
        );
    }
}
