<?php

declare(strict_types=1);

namespace NeNeRecords\Dashboard;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class GetDashboardSummaryHandler
{
    public function __construct(
        private GetDashboardSummaryUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $output = $this->useCase->execute();

        return $this->response->create([
            'recent_published' => array_map(
                static fn (DashboardRecentEntity $e): array => [
                    'id'               => $e->id,
                    'entity_type_id'   => $e->entityTypeId,
                    'entity_type_name' => $e->entityTypeName,
                    'entity_type_slug' => $e->entityTypeSlug,
                    'slug'             => $e->slug,
                    'published_at'     => $e->publishedAtIso,
                ],
                $output->recentPublished,
            ),
            'today_access_count'      => $output->todayAccessCount,
            'this_month_access_count' => $output->thisMonthAccessCount,
            'entity_type_summary' => array_map(
                static fn (DashboardEntityTypeSummary $s): array => [
                    'entity_type_id'   => $s->entityTypeId,
                    'entity_type_name' => $s->entityTypeName,
                    'entity_type_slug' => $s->entityTypeSlug,
                    'published_count'  => $s->publishedCount,
                    'draft_count'      => $s->draftCount,
                ],
                $output->entityTypeSummary,
            ),
        ]);
    }
}
