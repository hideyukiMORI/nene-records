<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

final readonly class DeleteMediaUseCase implements DeleteMediaUseCaseInterface
{
    public function __construct(
        private MediaRepositoryInterface $media,
        private StorageInterface $storage,
    ) {
    }

    public function execute(DeleteMediaInput $input): void
    {
        $media = $this->media->findById($input->id);

        if ($media === null) {
            throw new MediaNotFoundException($input->id);
        }

        // Best-effort: the storage driver tolerates an already-removed object.
        $this->storage->delete($this->storage->keyFromUrl($media->url));

        $this->media->delete($input->id);
    }
}
