<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

use DateTimeImmutable;
use Nene2\Http\ClockInterface;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\TextField\TextFieldRepositoryInterface;

final readonly class GetPopularEntitiesUseCase implements GetPopularEntitiesUseCaseInterface
{
    public function __construct(
        private AccessLogRepositoryInterface $accessLogs,
        private EntityRepositoryInterface $entities,
        private TextFieldRepositoryInterface $textFields,
        private ClockInterface $clock,
    ) {
    }

    public function execute(GetPopularEntitiesInput $input): GetPopularEntitiesOutput
    {
        $sinceDate = $this->clock->now()
            ->modify(sprintf('-%d days', max(0, $input->days - 1)))
            ->format('Y-m-d');

        $counts = $this->accessLogs->aggregateEntityViews($sinceDate);

        // Walk view counts (already desc) and keep only published, non-deleted
        // records until we have `limit` of them.
        $kept = [];
        foreach ($counts as $entityId => $viewCount) {
            if (count($kept) >= $input->limit) {
                break;
            }

            $entity = $this->entities->findById($entityId);
            if ($entity === null || $entity->isDeleted || $entity->status !== EntityStatus::Published) {
                continue;
            }

            $kept[$entityId] = ['entity' => $entity, 'viewCount' => $viewCount];
        }

        $titleByEntityId = $this->resolveTitles(array_keys($kept));

        $items = [];
        foreach ($kept as $entityId => $row) {
            $entity = $row['entity'];
            $items[] = new PopularEntityItem(
                entityId: $entityId,
                entityTypeId: $entity->entityTypeId,
                slug: $entity->slug,
                publishedAtIso: $entity->publishedAt?->format(DateTimeImmutable::ATOM),
                title: $titleByEntityId[$entityId] ?? null,
                viewCount: $row['viewCount'],
            );
        }

        return new GetPopularEntitiesOutput(items: $items);
    }

    /**
     * @param list<int> $entityIds
     *
     * @return array<int, string> entityId => title value
     */
    private function resolveTitles(array $entityIds): array
    {
        if ($entityIds === []) {
            return [];
        }

        $titles = [];
        foreach ($this->textFields->findByEntityIds($entityIds) as $field) {
            if ($field->fieldKey === 'title' && trim($field->value) !== '') {
                $titles[$field->entityId] = trim($field->value);
            }
        }

        return $titles;
    }
}
