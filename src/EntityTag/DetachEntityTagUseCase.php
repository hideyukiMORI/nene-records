<?php

declare(strict_types=1);

namespace NeNeRecords\EntityTag;

use NeNeRecords\Entity\EntityNotFoundException;
use NeNeRecords\Entity\EntityRepositoryInterface;

final readonly class DetachEntityTagUseCase implements DetachEntityTagUseCaseInterface
{
    public function __construct(
        private EntityRepositoryInterface $entities,
        private EntityTagRepositoryInterface $entityTags,
    ) {
    }

    public function execute(DetachEntityTagInput $input): void
    {
        if ($this->entities->findById($input->entityId) === null) {
            throw new EntityNotFoundException($input->entityId);
        }

        if (!$this->entityTags->isAttached($input->entityId, $input->tagId)) {
            throw new EntityTagNotAttachedException($input->entityId, $input->tagId);
        }

        $this->entityTags->detach($input->entityId, $input->tagId);
    }
}
