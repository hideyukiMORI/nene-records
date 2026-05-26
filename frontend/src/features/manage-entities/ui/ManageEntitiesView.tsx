import type { Entity, EntityRelationFilters, EntityStatus } from '@/entities/entity'
import type { RelationFieldDef } from '@/entities/field-def'
import type { Tag } from '@/entities/tag'
import { useTranslation } from '@/shared/i18n'
import { Button, ConfirmDialog, Stack, Text } from '@/shared/ui'
import { buildExportUrl } from '../lib/build-export-url'
import { EntityListPanel } from './EntityListPanel'
import { EntityRelationFilterPanel } from './EntityRelationFilterPanel'
import { EntityTagFilterPanel } from './EntityTagFilterPanel'

const ENTITY_STATUSES: EntityStatus[] = ['draft', 'published', 'scheduled', 'archived']

export interface ManageEntitiesViewProps {
  entityTypeId: number
  entityTypeSlug: string
  entityTypeName: string | null
  items: Entity[]
  recordLabels: Record<string, string>
  total: number
  page: number
  totalPages: number
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
  total,
  page,
  totalPages,
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

  const recordCountKey =
    total === 1 ? 'admin.entityRecords.recordCount.one' : 'admin.entityRecords.recordCount.other'

  // 検索・フィルターはコンテンツが存在するか、すでにフィルターが有効な場合のみ表示
  // （Progressive Disclosure: 空ページで絞り込みUI を見せない）
  const showFilters = total > 0 || isFilterActive

  return (
    <>
      <Stack gap="lg">
        {/* ── 検索 + フィルター（コンテンツがあるときのみ） ── */}
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
