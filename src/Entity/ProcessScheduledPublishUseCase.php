<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use LogicException;
use Nene2\Http\ClockInterface;

final readonly class ProcessScheduledPublishUseCase implements ProcessScheduledPublishUseCaseInterface
{
    public function __construct(
        private EntityRepositoryInterface $entities,
        private ClockInterface $clock,
    ) {
    }

    public function execute(): ProcessScheduledPublishOutput
    {
        $due = $this->entities->findDueScheduled();
        $now = $this->clock->now();
        $publishedIds = [];

        foreach ($due as $entity) {
            $entityId = $entity->id;

            if ($entityId === null) {
                throw new LogicException('Scheduled entity missing id.');
            }

            $updated = new Entity(
                id: $entityId,
                entityTypeId: $entity->entityTypeId,
                slug: $entity->slug,
                status: EntityStatus::Published,
                publishedAt: $entity->publishedAt ?? $now,
                isDeleted: $entity->isDeleted,
                deletedAt: $entity->deletedAt,
                metaTitle: $entity->metaTitle,
                metaDescription: $entity->metaDescription,
                scheduledAt: null,
            );

            $this->entities->update($updated);
            $publishedIds[] = $entityId;
        }

        return new ProcessScheduledPublishOutput(publishedIds: $publishedIds);
    }
}
