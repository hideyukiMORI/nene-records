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
        $rows = $this->entities->findAll($input->limit, $input->offset);

        $items = array_map(static function (Entity $entity): ListEntityItem {
            $entityId = $entity->id;

            if ($entityId === null) {
                throw new LogicException('Listed entity missing id.');
            }

            return new ListEntityItem(
                id: $entityId,
                entityTypeId: $entity->entityTypeId,
                isDeleted: $entity->isDeleted,
                deletedAtIso: $entity->deletedAt?->format(DateTimeInterface::ATOM),
            );
        }, $rows);

        return new ListEntitiesOutput(
            items: $items,
            limit: $input->limit,
            offset: $input->offset,
        );
    }
}
