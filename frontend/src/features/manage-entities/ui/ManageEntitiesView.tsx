import { useState } from 'react'
import type {
  Entity,
  EntityRelationFilters,
  EntitySortKey,
  EntitySortOrder,
  EntityStatus,
} from '@/entities/entity'
import type { RelationFieldDef } from '@/entities/field-def'
import type { Tag } from '@/entities/tag'
import { useTranslation } from '@/shared/i18n'
import { Button, ConfirmDialog, Select, Stack, Text } from '@/shared/ui'
import { buildExportUrl } from '../lib/build-export-url'
import { EntityListPanel } from './EntityListPanel'
import { EntityRelationFilterPanel } from './EntityRelationFilterPanel'
import { EntityTagFilterPanel } from './EntityTagFilterPanel'

const ENTITY_STATUSES: EntityStatus[] = ['draft', 'published', 'scheduled', 'archived']

interface SortOption {
  value: string
  key: EntitySortKey
  order: EntitySortOrder
  labelKey:
    | 'admin.entityRecords.sort.idDesc'
    | 'admin.entityRecords.sort.idAsc'
    | 'admin.entityRecords.sort.publishedDesc'
    | 'admin.entityRecords.sort.publishedAsc'
    | 'admin.entityRecords.sort.titleAsc'
    | 'admin.entityRecords.sort.titleDesc'
}

const SORT_OPTIONS: SortOption[] = [
  { value: 'id-desc', key: 'id', order: 'desc', labelKey: 'admin.entityRecords.sort.idDesc' },
  { value: 'id-asc', key: 'id', order: 'asc', labelKey: 'admin.entityRecords.sort.idAsc' },
  {
    value: 'published_at-desc',
    key: 'published_at',
    order: 'desc',
    labelKey: 'admin.entityRecords.sort.publishedDesc',
  },
  {
    value: 'published_at-asc',
    key: 'published_at',
    order: 'asc',
    labelKey: 'admin.entityRecords.sort.publishedAsc',
  },
  { value: 'title-asc', key: 'title', order: 'asc', labelKey: 'admin.entityRecords.sort.titleAsc' },
  {
    value: 'title-desc',
    key: 'title',
    order: 'desc',
    labelKey: 'admin.entityRecords.sort.titleDesc',
  },
]

export interface ManageEntitiesViewProps {
  entityTypeId: number
  entityTypeSlug: string
  entityTypeName: string | null
  items: Entity[]
  recordLabels: Record<string, string>
  recordBodyMap: Record<string, string>
  total: number
  page: number
  totalPages: number
  sortKey: EntitySortKey
  sortOrder: EntitySortOrder
  availableTags: Tag[]
  relationFieldDefs: RelationFieldDef[]
  selectedTagSlugs: string[]
  selectedRelationFilters: EntityRelationFilters
  selectedStatus: EntityStatus | undefined
  searchQuery: string
  isFilterActive: boolean
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  deleteTarget: Entity | null
  isDeleting: boolean
  onRetry: () => void
  onSortChange: (key: EntitySortKey, order: EntitySortOrder) => void
  onToggleTagSlug: (slug: string) => void
  onClearTagFilter: () => void
  onSelectRelationFilter: (fieldKey: string, targetEntityId: number | undefined) => void
  onClearRelationFilters: () => void
  onStatusChange: (status: EntityStatus | undefined) => void
  onSearchChange: (q: string) => void
  onPrevPage: (() => void) | undefined
  onNextPage: (() => void) | undefined
  onRequestDelete: (entity: Entity) => void
  onCancelDelete: () => void
  onConfirmDelete: () => Promise<void>
}

export function ManageEntitiesView({
  entityTypeId,
  entityTypeSlug,
  items,
  recordLabels,
  recordBodyMap,
  total,
  page,
  totalPages,
  sortKey,
  sortOrder,
  availableTags,
  relationFieldDefs,
  selectedTagSlugs,
  selectedRelationFilters,
  selectedStatus,
  searchQuery,
  isFilterActive,
  isLoading,
  isError,
  errorTitle,
  deleteTarget,
  isDeleting,
  onRetry,
  onSortChange,
  onToggleTagSlug,
  onClearTagFilter,
  onSelectRelationFilter,
  onClearRelationFilters,
  onStatusChange,
  onSearchChange,
  onPrevPage,
  onNextPage,
  onRequestDelete,
  onCancelDelete,
  onConfirmDelete,
}: ManageEntitiesViewProps) {
  const { t } = useTranslation()

  // アクティブなフィルター件数（検索を除く）
  const filterCount =
    selectedTagSlugs.length +
    Object.keys(selectedRelationFilters).length +
    (selectedStatus !== undefined ? 1 : 0)

  // 絞り込みアコーディオン — フィルターが有効なら初期展開
  const [isFilterOpen, setIsFilterOpen] = useState(() => filterCount > 0)

  const recordCountKey =
    total === 1 ? 'admin.entityRecords.recordCount.one' : 'admin.entityRecords.recordCount.other'

  // 検索・フィルターはコンテンツが存在するか、すでにフィルターが有効な場合のみ表示
  // （Progressive Disclosure: 空ページで絞り込みUI を見せない）
  const showFilters = total > 0 || isFilterActive

  const currentSortValue = `${sortKey}-${sortOrder}`

  return (
    <>
      <Stack gap="lg">
        {/* ── 検索 + ソート + フィルター（コンテンツがあるときのみ） ── */}
        {showFilters ? (
          <>
            {/* Search */}
            <div className="relative">
              <input
                type="search"
                value={searchQuery}
                onChange={(e) => {
                  onSearchChange(e.target.value)
                }}
                placeholder={t('admin.entityRecords.search.placeholder')}
                aria-label={t('admin.entityRecords.search.placeholder')}
                className="w-full rounded-md border border-border bg-surface-raised px-inline-md py-stack-sm font-sans text-body text-text-primary shadow-sm focus-visible:outline-none focus-visible:shadow-focus"
              />
              {searchQuery !== '' ? (
                <button
                  type="button"
                  onClick={() => {
                    onSearchChange('')
                  }}
                  aria-label={t('admin.entityRecords.search.clear')}
                  className="absolute right-2 top-1/2 -translate-y-1/2 rounded p-0.5 text-text-muted hover:text-text-primary"
                >
                  ✕
                </button>
              ) : null}
            </div>

            {/* Sort + Filter toggle toolbar */}
            <div className="flex items-center gap-inline-md">
              {/* 並び順セレクト */}
              <div className="flex items-center gap-1.5">
                <label
                  htmlFor="entity-sort-select"
                  className="shrink-0 font-sans text-caption text-text-muted"
                >
                  {t('admin.entityRecords.sort.label')}
                </label>
                <Select
                  id="entity-sort-select"
                  size="sm"
                  value={currentSortValue}
                  onChange={(e) => {
                    const opt = SORT_OPTIONS.find((o) => o.value === e.target.value)
                    if (opt !== undefined) {
                      onSortChange(opt.key, opt.order)
                    }
                  }}
                >
                  {SORT_OPTIONS.map((opt) => (
                    <option key={opt.value} value={opt.value}>
                      {t(opt.labelKey)}
                    </option>
                  ))}
                </Select>
              </div>

              <div className="flex-1" />

              {/* 絞り込みアコーディオン トグル */}
              <button
                type="button"
                onClick={() => {
                  setIsFilterOpen((v) => !v)
                }}
                aria-expanded={isFilterOpen}
                aria-controls="filter-accordion"
                className="inline-flex items-center gap-1 font-sans text-caption text-text-muted transition-colors hover:text-text-primary"
              >
                {t('admin.entityRecords.filter.toggle')}
                {filterCount > 0 ? (
                  <span className="rounded-full bg-accent px-1.5 py-px text-xs font-medium text-white">
                    {t('admin.entityRecords.filter.active', { count: filterCount })}
                  </span>
                ) : null}
                <span aria-hidden="true" className="text-xs">
                  {isFilterOpen ? '▲' : '▼'}
                </span>
              </button>
            </div>

            {/* 絞り込みアコーディオン */}
            {isFilterOpen ? (
              <div id="filter-accordion">
                <Stack gap="md">
                  {/* Status Filter */}
                  <Stack gap="sm">
                    <Text as="h2" variant="heading-sm">
                      {t('admin.entityRecords.statusFilter.label')}
                    </Text>
                    <div className="flex flex-wrap gap-inline-sm">
                      <Button
                        variant={selectedStatus === undefined ? 'primary' : 'secondary'}
                        size="sm"
                        aria-pressed={selectedStatus === undefined}
                        onClick={() => {
                          onStatusChange(undefined)
                        }}
                      >
                        {t('admin.entityRecords.statusFilter.all')}
                      </Button>
                      {ENTITY_STATUSES.map((status) => (
                        <Button
                          key={status}
                          variant={selectedStatus === status ? 'primary' : 'secondary'}
                          size="sm"
                          aria-pressed={selectedStatus === status}
                          onClick={() => {
                            onStatusChange(status)
                          }}
                        >
                          {t(`admin.entityStatus.status.${status}`)}
                        </Button>
                      ))}
                    </div>
                  </Stack>

                  <EntityTagFilterPanel
                    tags={availableTags}
                    selectedTagSlugs={selectedTagSlugs}
                    onToggleTagSlug={onToggleTagSlug}
                    onClear={onClearTagFilter}
                  />
                  <EntityRelationFilterPanel
                    relationFieldDefs={relationFieldDefs}
                    selectedFilters={selectedRelationFilters}
                    onSelectTarget={onSelectRelationFilter}
                    onClear={onClearRelationFilters}
                  />
                </Stack>
              </div>
            ) : null}
          </>
        ) : null}

        {/* ── リスト一覧セクション ── */}
        <Stack gap="sm">
          {/* セクション見出し：件数 + エクスポート */}
          <div className="flex items-center justify-between gap-4">
            <Text as="h2" variant="heading-sm">
              {t(recordCountKey, { count: total })}
            </Text>
            {total > 0 ? (
              <div className="flex shrink-0 gap-2">
                <div className="group relative">
                  <a
                    href={buildExportUrl(entityTypeId, 'csv', searchQuery, selectedStatus)}
                    download="records.csv"
                    aria-label={t('admin.entityRecords.export.csv.tooltip')}
                    className="inline-flex items-center gap-1 rounded-md border border-border bg-surface-raised px-inline-sm py-stack-xs font-sans text-caption text-text-muted shadow-sm transition-colors hover:text-text-primary"
                  >
                    <span aria-hidden="true">↓</span>
                    {t('admin.entityRecords.export.csv')}
                  </a>
                  <span
                    role="tooltip"
                    className="pointer-events-none absolute bottom-full right-0 mb-1.5 w-max max-w-56 rounded-md bg-text-primary px-2 py-1 font-sans text-caption text-white opacity-0 shadow-md transition-opacity duration-fast group-hover:opacity-100"
                  >
                    {t('admin.entityRecords.export.csv.tooltip')}
                  </span>
                </div>
                <div className="group relative">
                  <a
                    href={buildExportUrl(entityTypeId, 'json', searchQuery, selectedStatus)}
                    download="records.json"
                    aria-label={t('admin.entityRecords.export.json.tooltip')}
                    className="inline-flex items-center gap-1 rounded-md border border-border bg-surface-raised px-inline-sm py-stack-xs font-sans text-caption text-text-muted shadow-sm transition-colors hover:text-text-primary"
                  >
                    <span aria-hidden="true">↓</span>
                    {t('admin.entityRecords.export.json')}
                  </a>
                  <span
                    role="tooltip"
                    className="pointer-events-none absolute bottom-full right-0 mb-1.5 w-max max-w-56 rounded-md bg-text-primary px-2 py-1 font-sans text-caption text-white opacity-0 shadow-md transition-opacity duration-fast group-hover:opacity-100"
                  >
                    {t('admin.entityRecords.export.json.tooltip')}
                  </span>
                </div>
              </div>
            ) : null}
          </div>

          <EntityListPanel
            entityTypeSlug={entityTypeSlug}
            items={items}
            recordLabels={recordLabels}
            recordBodyMap={recordBodyMap}
            isLoading={isLoading}
            isError={isError}
            errorTitle={errorTitle}
            isDeleting={isDeleting}
            isFilterActive={isFilterActive}
            onRetry={onRetry}
            onDelete={onRequestDelete}
          />

          {totalPages > 1 || page > 0 ? (
            <div className="flex items-center justify-between gap-inline-md pt-stack-xs">
              <Button
                variant="secondary"
                size="sm"
                disabled={onPrevPage === undefined}
                onClick={onPrevPage}
              >
                {t('admin.entityRecords.pagination.prev')}
              </Button>
              <Text muted>
                {t('admin.entityRecords.pagination.page', {
                  page: page + 1,
                  total: totalPages,
                })}
              </Text>
              <Button
                variant="secondary"
                size="sm"
                disabled={onNextPage === undefined}
                onClick={onNextPage}
              >
                {t('admin.entityRecords.pagination.next')}
              </Button>
            </div>
          ) : null}
        </Stack>
      </Stack>

      <ConfirmDialog
        open={deleteTarget !== null}
        title={t('admin.entityRecords.delete.title')}
        description={
          deleteTarget !== null
            ? t('admin.entityRecords.delete.description', { id: deleteTarget.id })
            : undefined
        }
        confirmLabel={isDeleting ? t('common.actions.deleting') : t('common.actions.delete')}
        cancelLabel={t('common.actions.cancel')}
        isPending={isDeleting}
        onCancel={onCancelDelete}
        onConfirm={() => {
          void onConfirmDelete()
        }}
      />
    </>
  )
}
