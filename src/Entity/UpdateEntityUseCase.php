<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use LogicException;
use NeNeRecords\EntityType\EntityTypeNotFoundException;
use NeNeRecords\EntityType\EntityTypeRepositoryInterface;

final readonly class UpdateEntityUseCase implements UpdateEntityUseCaseInterface
{
    public function __construct(
        private EntityRepositoryInterface $entities,
        private EntityTypeRepositoryInterface $entityTypes,
    ) {
    }

    public function execute(UpdateEntityInput $input): UpdateEntityOutput
    {
        $existing = $this->entities->findById($input->id);

        if ($existing === null) {
            throw new EntityNotFoundException($input->id);
        }

        $entityId = $existing->id;

        if ($entityId === null) {
            throw new LogicException('Loaded entity missing id.');
        }

        if ($this->entityTypes->findById($input->entityTypeId) === null) {
            throw new EntityTypeNotFoundException($input->entityTypeId);
        }

        // Auto-set published_at when transitioning to published for the first time.
        $publishedAt = $input->publishedAt ?? $existing->publishedAt;
        if ($input->status === EntityStatus::PUBLISHED && $publishedAt === null) {
            $publishedAt = new DateTimeImmutable();
        }

        $updated = new Entity(
            id: $entityId,
            entityTypeId: $input->entityTypeId,
            status: $input->status,
            publishedAt: $publishedAt,
            isDeleted: $existing->isDeleted,
            deletedAt: $existing->deletedAt,
        );

        $this->entities->update($updated);

        return new UpdateEntityOutput(
            id: $entityId,
            entityTypeId: $input->entityTypeId,
            status: $input->status,
            publishedAtIso: $publishedAt?->format(DateTimeInterface::ATOM),
            isDeleted: $existing->isDeleted,
            deletedAtIso: $existing->deletedAt?->format(DateTimeInterface::ATOM),
        );
    }
}
