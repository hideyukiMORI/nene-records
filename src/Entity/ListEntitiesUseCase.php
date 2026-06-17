<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use DateTimeInterface;
use LogicException;

final readonly class ListEntitiesUseCase implements ListEntitiesUseCaseInterface
{
    public function __construct(
        private EntityRepositoryInterface $entities,
    ) {
    }

    public function execute(ListEntitiesInput $input): ListEntitiesOutput
    {
        $rows = $this->entities->findByCriteria($input->criteria, $input->limit, $input->offset);
        $total = $this->entities->countByCriteria($input->criteria);

        $items = array_map(static function (Entity $entity): ListEntityItem {
            $entityId = $entity->id;

            if ($entityId === null) {
                throw new LogicException('Listed entity missing id.');
            }

            return new ListEntityItem(
                id: $entityId,
                entityTypeId: $entity->entityTypeId,
                slug: $entity->slug,
                status: $entity->status->value,
                publishedAtIso: $entity->publishedAt?->format(DateTimeInterface::ATOM),
                isDeleted: $entity->isDeleted,
                deletedAtIso: $entity->deletedAt?->format(DateTimeInterface::ATOM),
                scheduledAtIso: $entity->scheduledAt?->format(DateTimeInterface::ATOM),
                createdAtIso: $entity->createdAt?->format(DateTimeInterface::ATOM),
                updatedAtIso: $entity->updatedAt?->format(DateTimeInterface::ATOM),
                metaTitle: $entity->metaTitle,
                metaDescription: $entity->metaDescription,
            );
        }, $rows);

        return new ListEntitiesOutput(
            items: $items,
            limit: $input->limit,
            offset: $input->offset,
            total: $total,
        );
    }
}
