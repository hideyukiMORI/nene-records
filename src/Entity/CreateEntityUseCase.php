<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use NeNeRecords\EntityType\EntityTypeNotFoundException;
use NeNeRecords\EntityType\EntityTypeRepositoryInterface;

final readonly class CreateEntityUseCase implements CreateEntityUseCaseInterface
{
    public function __construct(
        private EntityRepositoryInterface $entities,
        private EntityTypeRepositoryInterface $entityTypes,
    ) {
    }

    public function execute(CreateEntityInput $input): CreateEntityOutput
    {
        if ($this->entityTypes->findById($input->entityTypeId) === null) {
            throw new EntityTypeNotFoundException($input->entityTypeId);
        }

        $id = $this->entities->save(new Entity(id: null, entityTypeId: $input->entityTypeId));

        return new CreateEntityOutput(
            id: $id,
            entityTypeId: $input->entityTypeId,
            isDeleted: false,
            deletedAtIso: null,
        );
    }
}
