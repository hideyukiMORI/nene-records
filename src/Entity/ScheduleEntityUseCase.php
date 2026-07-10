<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use DateTimeInterface;
use InvalidArgumentException;
use LogicException;
use Nene2\Http\ClockInterface;

final readonly class ScheduleEntityUseCase implements ScheduleEntityUseCaseInterface
{
    public function __construct(
        private EntityRepositoryInterface $entities,
        private ClockInterface $clock,
    ) {
    }

    public function execute(ScheduleEntityInput $input): ScheduleEntityOutput
    {
        $existing = $this->entities->findById($input->id);

        if ($existing === null) {
            throw new EntityNotFoundException($input->id);
        }

        $entityId = $existing->id;

        if ($entityId === null) {
            throw new LogicException('Loaded entity missing id.');
        }

        if ($input->scheduledAt <= $this->clock->now()) {
            throw new InvalidArgumentException('scheduled_at must be in the future.');
        }

        // repository::update() is full-replace: every column it writes must be
        // carried from $existing or it is silently nulled (#776).
        $updated = new Entity(
            id: $entityId,
            entityTypeId: $existing->entityTypeId,
            slug: $existing->slug,
            permalink: $existing->permalink,
            layout: $existing->layout,
            status: EntityStatus::Scheduled,
            publishedAt: $existing->publishedAt,
            isDeleted: $existing->isDeleted,
            deletedAt: $existing->deletedAt,
            metaTitle: $existing->metaTitle,
            metaDescription: $existing->metaDescription,
            scheduledAt: $input->scheduledAt,
            showComments: $existing->showComments,
            showRelated: $existing->showRelated,
        );

        $this->entities->update($updated);

        return new ScheduleEntityOutput(
            id: $entityId,
            status: EntityStatus::Scheduled->value,
            scheduledAtIso: $input->scheduledAt->format(DateTimeInterface::ATOM),
        );
    }
}
