import type {
  AccessStatsByDateDto,
  DashboardEntityTypeSummaryDto,
  DashboardRecentEntityDto,
  DashboardSummaryDto,
} from './api-types'
import type {
  AccessStatsByDate,
  DashboardEntityTypeSummary,
  DashboardRecentEntity,
  DashboardSummary,
} from './model'

function mapRecentEntityDtoToModel(dto: DashboardRecentEntityDto): DashboardRecentEntity {
  return {
    id: dto.id,
    entityTypeId: dto.entity_type_id,
    entityTypeName: dto.entity_type_name,
    entityTypeSlug: dto.entity_type_slug,
    slug: dto.slug,
    publishedAt: dto.published_at,
  }
}

function mapEntityTypeSummaryDtoToModel(
  dto: DashboardEntityTypeSummaryDto,
): DashboardEntityTypeSummary {
  return {
    entityTypeId: dto.entity_type_id,
    entityTypeName: dto.entity_type_name,
    entityTypeSlug: dto.entity_type_slug,
    publishedCount: dto.published_count,
    draftCount: dto.draft_count,
  }
}

export function mapDashboardSummaryDtoToModel(dto: DashboardSummaryDto): DashboardSummary {
  return {
    recentPublished: dto.recent_published.map(mapRecentEntityDtoToModel),
    todayAccessCount: dto.today_access_count,
    thisMonthAccessCount: dto.this_month_access_count,
    entityTypeSummary: dto.entity_type_summary.map(mapEntityTypeSummaryDtoToModel),
  }
}

export function mapAccessStatsByDateDtoToModel(dto: AccessStatsByDateDto): AccessStatsByDate {
  return {
    from: dto.from,
    to: dto.to,
    items: dto.items.map((item) => ({
      date: item.date,
      requestCount: item.request_count,
      avgDurationMs: item.avg_duration_ms,
    })),
  }
}
