<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

final readonly class DeleteMediaUseCase implements DeleteMediaUseCaseInterface
{
    public function __construct(
        private MediaRepositoryInterface $media,
    ) {
    }

    public function execute(DeleteMediaInput $input): void
    {
        $media = $this->media->findById($input->id);

        if ($media === null) {
            throw new MediaNotFoundException($input->id);
        }

        // Delete physical file (best-effort — don't fail if already removed)
        $absolutePath = rtrim($input->storageRoot, '/') . '/' . ltrim($media->url, '/media/');
        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }

        $this->media->delete($input->id);
    }
}
