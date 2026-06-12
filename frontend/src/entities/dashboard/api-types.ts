export interface DashboardRecentEntityDto {
  id: number
  entity_type_id: number
  entity_type_name: string
  entity_type_slug: string
  slug: string | null
  published_at: string | null
}

export interface DashboardEntityTypeSummaryDto {
  entity_type_id: number
  entity_type_name: string
  entity_type_slug: string
  published_count: number
  draft_count: number
}

export interface DashboardSummaryDto {
  recent_published: DashboardRecentEntityDto[]
  today_access_count: number
  this_month_access_count: number
  entity_type_summary: DashboardEntityTypeSummaryDto[]
}

export interface AccessStatsDayItemDto {
  date: string
  request_count: number
  avg_duration_ms: number
}

export interface AccessStatsByDateDto {
  from: string
  to: string
  items: AccessStatsDayItemDto[]
}
