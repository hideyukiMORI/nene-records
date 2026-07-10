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

            // repository::update() is full-replace: every column it writes must be
            // carried from the loaded entity or it is silently nulled (#776).
            $updated = new Entity(
                id: $entityId,
                entityTypeId: $entity->entityTypeId,
                slug: $entity->slug,
                permalink: $entity->permalink,
                layout: $entity->layout,
                status: EntityStatus::Published,
                publishedAt: $entity->publishedAt ?? $now,
                isDeleted: $entity->isDeleted,
                deletedAt: $entity->deletedAt,
                metaTitle: $entity->metaTitle,
                metaDescription: $entity->metaDescription,
                scheduledAt: null,
                showComments: $entity->showComments,
                showRelated: $entity->showRelated,
            );

            $this->entities->update($updated);
            $publishedIds[] = $entityId;
        }

        return new ProcessScheduledPublishOutput(publishedIds: $publishedIds);
    }
}
