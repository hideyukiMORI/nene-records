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
            slug: $entity->slug,
            status: $entity->status->value,
            publishedAtIso: $entity->publishedAt?->format(DateTimeInterface::ATOM),
            isDeleted: $entity->isDeleted,
            deletedAtIso: $entity->deletedAt?->format(DateTimeInterface::ATOM),
            metaTitle: $entity->metaTitle,
            metaDescription: $entity->metaDescription,
            scheduledAtIso: $entity->scheduledAt?->format(DateTimeInterface::ATOM),
            createdAtIso: $entity->createdAt?->format(DateTimeInterface::ATOM),
            updatedAtIso: $entity->updatedAt?->format(DateTimeInterface::ATOM),
            layout: $entity->layout,
            permalink: $entity->permalink,
        );
    }
}
