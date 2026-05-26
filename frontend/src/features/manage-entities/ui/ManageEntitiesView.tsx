import type { Entity, EntityRelationFilters } from '@/entities/entity'
import type { RelationFieldDef } from '@/entities/field-def'
import type { Tag } from '@/entities/tag'
import { useTranslation } from '@/shared/i18n'
import { ConfirmDialog, Stack, Text } from '@/shared/ui'
import { EntityCreatePanel } from './EntityCreatePanel'
import { EntityListPanel } from './EntityListPanel'
import { EntityRelationFilterPanel } from './EntityRelationFilterPanel'
import { EntityTagFilterPanel } from './EntityTagFilterPanel'

export interface ManageEntitiesViewProps {
  entityTypeId: number
  entityTypeName: string | null
  entityTypeSlug: string | null
  items: Entity[]
  recordLabels: Record<string, string>
  total: number
  availableTags: Tag[]
  relationFieldDefs: RelationFieldDef[]
  selectedTagSlugs: string[]
  selectedRelationFilters: EntityRelationFilters
  searchQuery: string
  isFilterActive: boolean
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  isCreating: boolean
  createErrorTitle: string | null
  deleteTarget: Entity | null
  isDeleting: boolean
  onRetry: () => void
  onToggleTagSlug: (slug: string) => void
  onClearTagFilter: () => void
  onSelectRelationFilter: (fieldKey: string, targetEntityId: number | undefined) => void
  onClearRelationFilters: () => void
  onSearchChange: (q: string) => void
  onCreate: () => Promise<void>
  onRequestDelete: (entity: Entity) => void
  onCancelDelete: () => void
  onConfirmDelete: () => Promise<void>
}

export function ManageEntitiesView({
  entityTypeId,
  entityTypeName,
  entityTypeSlug,
  items,
  recordLabels,
  total,
  availableTags,
  relationFieldDefs,
  selectedTagSlugs,
  selectedRelationFilters,
  searchQuery,
  isFilterActive,
  isLoading,
  isError,
  errorTitle,
  isCreating,
  createErrorTitle,
  deleteTarget,
  isDeleting,
  onRetry,
  onToggleTagSlug,
  onClearTagFilter,
  onSelectRelationFilter,
  onClearRelationFilters,
  onSearchChange,
  onCreate,
  onRequestDelete,
  onCancelDelete,
  onConfirmDelete,
}: ManageEntitiesViewProps) {
  const { t } = useTranslation()

  const recordCountKey =
    total === 1 ? 'admin.entityRecords.recordCount.one' : 'admin.entityRecords.recordCount.other'

  return (
    <>
      <Stack gap="lg">
        <Stack gap="xs">
          <Text as="p" muted>
            {entityTypeSlug ?? '…'}
          </Text>
          <Text as="p" muted>
            {t(recordCountKey, { count: total })}
          </Text>
        </Stack>
        <EntityCreatePanel
          isSubmitting={isCreating}
          serverErrorTitle={createErrorTitle}
          onCreate={onCreate}
        />

        {/* ── Search ── */}
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
        <Stack gap="sm">
          <Text as="h2" variant="heading-sm">
            {entityTypeName !== null
              ? t('admin.entityRecords.list.title', { name: entityTypeName })
              : t('admin.entityRecords.list.titleDefault')}
          </Text>
          <EntityListPanel
            entityTypeId={entityTypeId}
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
