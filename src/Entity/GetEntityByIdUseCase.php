<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use DateTimeInterface;
use LogicException;

final readonly class GetEntityByIdUseCase implements GetEntityByIdUseCaseInterface
{
    public function __construct(
        private EntityRepositoryInterface $entities,
    ) {
    }

    public function execute(GetEntityByIdInput $input): GetEntityByIdOutput
    {
        $entity = $this->entities->findById($input->id);

        if ($entity === null) {
            throw new EntityNotFoundException($input->id);
        }

        $entityId = $entity->id;

        if ($entityId === null) {
            throw new LogicException('Loaded entity missing id.');
        }

        return new GetEntityByIdOutput(
            id: $entityId,
            entityTypeId: $entity->entityTypeId,
            isDeleted: $entity->isDeleted,
            deletedAtIso: $entity->deletedAt?->format(DateTimeInterface::ATOM),
        );
    }
}
