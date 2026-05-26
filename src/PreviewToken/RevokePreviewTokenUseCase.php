<?php

declare(strict_types=1);

namespace NeNeRecords\PreviewToken;

use NeNeRecords\Entity\EntityNotFoundException;
use NeNeRecords\Entity\EntityRepositoryInterface;

final readonly class RevokePreviewTokenUseCase implements RevokePreviewTokenUseCaseInterface
{
    public function __construct(
        private EntityRepositoryInterface $entities,
        private EntityPreviewTokenRepositoryInterface $previewTokens,
    ) {
    }

    public function execute(RevokePreviewTokenInput $input): void
    {
        $entity = $this->entities->findById($input->entityId);

        if ($entity === null || $entity->isDeleted) {
            throw new EntityNotFoundException($input->entityId);
        }

        $this->previewTokens->deleteByEntityId($input->entityId);
    }
}
