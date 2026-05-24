import type { Entity } from '@/entities/entity'
import type { Tag } from '@/entities/tag'
import { ConfirmDialog, Stack, Text } from '@/shared/ui'
import { EntityCreatePanel } from './EntityCreatePanel'
import { EntityListPanel } from './EntityListPanel'
import { EntityTagFilterPanel } from './EntityTagFilterPanel'

export interface ManageEntitiesViewProps {
  entityTypeId: number
  entityTypeName: string | null
  entityTypeSlug: string | null
  items: Entity[]
  recordLabels: Record<string, string>
  total: number
  availableTags: Tag[]
  selectedTagSlugs: string[]
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
  selectedTagSlugs,
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
  onCreate,
  onRequestDelete,
  onCancelDelete,
  onConfirmDelete,
}: ManageEntitiesViewProps) {
  return (
    <>
      <Stack gap="lg">
        <Stack gap="xs">
          <Text as="p" muted>
            {entityTypeSlug ?? '…'}
          </Text>
          <Text as="p" muted>
            {total} record{total === 1 ? '' : 's'}
          </Text>
        </Stack>
        <EntityCreatePanel
          isSubmitting={isCreating}
          serverErrorTitle={createErrorTitle}
          onCreate={onCreate}
        />
        <EntityTagFilterPanel
          tags={availableTags}
          selectedTagSlugs={selectedTagSlugs}
          onToggleTagSlug={onToggleTagSlug}
          onClear={onClearTagFilter}
        />
        <Stack gap="sm">
          <Text as="h2" variant="heading-sm">
            {entityTypeName !== null ? `${entityTypeName} records` : 'Records'}
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
        title="Delete record?"
        description={
          deleteTarget !== null
            ? `Record #${String(deleteTarget.id)} will be soft-deleted.`
            : undefined
        }
        confirmLabel={isDeleting ? 'Deleting…' : 'Delete'}
        isPending={isDeleting}
        onCancel={onCancelDelete}
        onConfirm={() => {
          void onConfirmDelete()
        }}
      />
    </>
  )
}
