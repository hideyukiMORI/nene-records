<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

final readonly class ListMediaUseCase implements ListMediaUseCaseInterface
{
    public function __construct(
        private MediaRepositoryInterface $media,
    ) {
    }

    public function execute(): ListMediaOutput
    {
        $items = array_map(
            static fn (Media $m): ListMediaItem => new ListMediaItem(
                id: (int) $m->id,
                url: $m->url,
                originalName: $m->originalName,
                mimeType: $m->mimeType,
                size: $m->size,
                createdAt: $m->createdAt,
                width: $m->width,
                height: $m->height,
                altText: $m->altText,
            ),
            $this->media->list(),
        );

        return new ListMediaOutput($items);
    }
}
