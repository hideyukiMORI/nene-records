<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

final readonly class FindMediaUsagesUseCase implements FindMediaUsagesUseCaseInterface
{
    public function __construct(
        private MediaRepositoryInterface $media,
    ) {
    }

    /**
     * @return list<MediaUsage>
     */
    public function execute(int $id): array
    {
        $media = $this->media->findById($id);

        if ($media === null) {
            throw new MediaNotFoundException($id);
        }

        return $this->media->findUsages($media->url);
    }
}
