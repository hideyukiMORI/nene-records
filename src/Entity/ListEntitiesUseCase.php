<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use LogicException;
use NeNeRecords\Analytics\AccessLogRepositoryInterface;

final readonly class ListEntitiesUseCase implements ListEntitiesUseCaseInterface
{
    /** Rolling window for the opt-in per-record view counts (#674). */
    private const VIEW_WINDOW_DAYS = 30;

    public function __construct(
        private EntityRepositoryInterface $entities,
        /** Optional — enables `includeViews` (per-record view counts, #674). */
        private ?AccessLogRepositoryInterface $accessLogs = null,
    ) {
    }

    public function execute(ListEntitiesInput $input): ListEntitiesOutput
    {
        $rows = $this->entities->findByCriteria($input->criteria, $input->limit, $input->offset);
        $total = $this->entities->countByCriteria($input->criteria);

        // Per-record view counts (#674) — the same metric as the analytics "popular"
        // view. Opt-in so only callers that ask for it pay the GROUP BY aggregation.
        $viewCounts = [];
        if ($input->includeViews && $this->accessLogs !== null) {
            $sinceDate = (new DateTimeImmutable())
                ->modify(sprintf('-%d days', self::VIEW_WINDOW_DAYS - 1))
                ->format('Y-m-d');
            $viewCounts = $this->accessLogs->aggregateEntityViews($sinceDate);
        }

        $items = array_map(static function (Entity $entity) use ($viewCounts): ListEntityItem {
            $entityId = $entity->id;

            if ($entityId === null) {
                throw new LogicException('Listed entity missing id.');
            }

            return new ListEntityItem(
                id: $entityId,
                entityTypeId: $entity->entityTypeId,
                slug: $entity->slug,
                permalink: $entity->permalink,
                status: $entity->status->value,
                publishedAtIso: $entity->publishedAt?->format(DateTimeInterface::ATOM),
                isDeleted: $entity->isDeleted,
                deletedAtIso: $entity->deletedAt?->format(DateTimeInterface::ATOM),
                scheduledAtIso: $entity->scheduledAt?->format(DateTimeInterface::ATOM),
                createdAtIso: $entity->createdAt?->format(DateTimeInterface::ATOM),
                updatedAtIso: $entity->updatedAt?->format(DateTimeInterface::ATOM),
                metaTitle: $entity->metaTitle,
                metaDescription: $entity->metaDescription,
                menuOrder: $entity->menuOrder,
                viewCount: $viewCounts[$entityId] ?? 0,
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
