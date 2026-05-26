<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use LogicException;

final readonly class UnscheduleEntityUseCase implements UnscheduleEntityUseCaseInterface
{
    public function __construct(
        private EntityRepositoryInterface $entities,
    ) {
    }

    public function execute(int $entityId): void
    {
        $existing = $this->entities->findById($entityId);

        if ($existing === null) {
            throw new EntityNotFoundException($entityId);
        }

        $id = $existing->id;

        if ($id === null) {
            throw new LogicException('Loaded entity missing id.');
        }

        $updated = new Entity(
            id: $id,
            entityTypeId: $existing->entityTypeId,
            slug: $existing->slug,
            status: EntityStatus::Draft,
            publishedAt: $existing->publishedAt,
            isDeleted: $existing->isDeleted,
            deletedAt: $existing->deletedAt,
            metaTitle: $existing->metaTitle,
            metaDescription: $existing->metaDescription,
            scheduledAt: null,
        );

        $this->entities->update($updated);
    }
}
