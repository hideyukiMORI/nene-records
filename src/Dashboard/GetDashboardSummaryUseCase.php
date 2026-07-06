<?php

declare(strict_types=1);

namespace NeNeRecords\Dashboard;

use DateTimeInterface;
use Nene2\Http\ClockInterface;
use NeNeRecords\Analytics\AccessLogRepositoryInterface;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\EntityType\EntityTypeRepositoryInterface;

final readonly class GetDashboardSummaryUseCase implements GetDashboardSummaryUseCaseInterface
{
    private const RECENT_PUBLISHED_LIMIT = 5;

    private const ENTITY_TYPE_PAGE_SIZE = 100;

    public function __construct(
        private EntityRepositoryInterface $entities,
        private EntityTypeRepositoryInterface $entityTypes,
        private AccessLogRepositoryInterface $accessLogs,
        private ClockInterface $clock,
    ) {
    }

    public function execute(): GetDashboardSummaryOutput
    {
        $now = $this->clock->now();

        // ── Recent published entities ──────────────────────────────────────
        $recentEntities = $this->entities->findRecentPublished(self::RECENT_PUBLISHED_LIMIT);

        // Pre-load entity types for name/slug lookup
        $entityTypeRows = $this->entityTypes->findAll(self::ENTITY_TYPE_PAGE_SIZE, 0);
        $entityTypeMap = [];
        foreach ($entityTypeRows as $et) {
            if ($et->id !== null) {
                $entityTypeMap[$et->id] = $et;
            }
        }

        $recentPublished = array_map(
            static function (\NeNeRecords\Entity\Entity $entity) use ($entityTypeMap): DashboardRecentEntity {
                $et = $entityTypeMap[$entity->entityTypeId] ?? null;

                return new DashboardRecentEntity(
                    id: (int) $entity->id,
                    entityTypeId: $entity->entityTypeId,
                    entityTypeName: $et !== null ? $et->name : '',
                    entityTypeSlug: $et !== null ? $et->slug : '',
                    slug: $entity->slug,
                    publishedAtIso: $entity->publishedAt?->format(DateTimeInterface::ATOM),
                );
            },
            $recentEntities,
        );

        // ── Access counts ──────────────────────────────────────────────────
        $todayAccessCount = $this->accessLogs->countByDate($now);
        $thisMonthAccessCount = $this->accessLogs->countByYearMonth(
            (int) $now->format('Y'),
            (int) $now->format('n'),
        );

        // ── Entity type summary ────────────────────────────────────────────
        $publishedByType = $this->entities->countPublishedGroupedByEntityType();
        $draftByType     = $this->entities->countDraftGroupedByEntityType();

        $entityTypeSummary = array_map(
            static function (\NeNeRecords\EntityType\EntityType $et) use ($publishedByType, $draftByType): DashboardEntityTypeSummary {
                $typeId = (int) $et->id;

                return new DashboardEntityTypeSummary(
                    entityTypeId: $typeId,
                    entityTypeName: $et->name,
                    entityTypeSlug: $et->slug,
                    publishedCount: $publishedByType[$typeId] ?? 0,
                    draftCount: $draftByType[$typeId] ?? 0,
                );
            },
            $entityTypeRows,
        );

        return new GetDashboardSummaryOutput(
            recentPublished: $recentPublished,
            todayAccessCount: $todayAccessCount,
            thisMonthAccessCount: $thisMonthAccessCount,
            entityTypeSummary: $entityTypeSummary,
        );
    }
}
