export interface DashboardRecentEntity {
  id: number
  entityTypeId: number
  entityTypeName: string
  entityTypeSlug: string
  slug: string | null
  publishedAt: string | null
}

export interface DashboardEntityTypeSummary {
  entityTypeId: number
  entityTypeName: string
  entityTypeSlug: string
  publishedCount: number
  draftCount: number
}

export interface DashboardSummary {
  recentPublished: DashboardRecentEntity[]
  todayAccessCount: number
  thisMonthAccessCount: number
  entityTypeSummary: DashboardEntityTypeSummary[]
}
