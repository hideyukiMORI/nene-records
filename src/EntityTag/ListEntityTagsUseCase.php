<?php

declare(strict_types=1);

namespace NeNeRecords\EntityTag;

use NeNeRecords\Entity\EntityNotFoundException;
use NeNeRecords\Entity\EntityRepositoryInterface;

final readonly class ListEntityTagsUseCase implements ListEntityTagsUseCaseInterface
{
    public function __construct(
        private EntityRepositoryInterface $entities,
        private EntityTagRepositoryInterface $entityTags,
    ) {
    }

    public function execute(ListEntityTagsInput $input): ListEntityTagsOutput
    {
        if ($this->entities->findById($input->entityId) === null) {
            throw new EntityNotFoundException($input->entityId);
        }

        return new ListEntityTagsOutput(
            items: $this->entityTags->findTagsByEntityId($input->entityId),
        );
    }
}
