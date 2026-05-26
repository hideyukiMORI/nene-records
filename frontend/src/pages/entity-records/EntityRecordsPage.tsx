import { Link, useParams } from 'react-router-dom'
import { toEntityTypeId, useEntityType } from '@/entities/entity-type'
import { ManageEntitiesView, useManageEntitiesPage } from '@/features/manage-entities'
import { useTranslation } from '@/shared/i18n'
import { Button, Stack, Text } from '@/shared/ui'

export function EntityRecordsPage() {
  const { t } = useTranslation()
  const { entityTypeId: entityTypeIdParam } = useParams()
  const entityTypeId = Number(entityTypeIdParam)

  const entityTypeQuery = useEntityType(toEntityTypeId(entityTypeId))
  const {
    items,
    recordLabels,
    total,
    availableTags,
    relationFieldDefs,
    selectedTagSlugs,
    selectedRelationFilters,
    selectedStatus,
    setStatus,
    searchQuery,
    setSearchQuery,
    isFilterActive,
    isLoading,
    isError,
    errorTitle,
    refetch,
    createEntity,
    isCreating,
    createErrorTitle,
    toggleTagSlug,
    clearTagFilter,
    setRelationFilter,
    clearRelationFilters,
    deleteTarget,
    requestDelete,
    cancelDelete,
    confirmDelete,
    isDeleting,
  } = useManageEntitiesPage(entityTypeId)

  return (
    <Stack gap="md">
      <Stack gap="sm">
        <Link to="/entity-types">
          <Button variant="secondary" size="sm">
            {t('admin.entityRecords.backToTypes')}
          </Button>
        </Link>
        <Text as="h1" variant="heading-md">
          {entityTypeQuery.data?.name ?? t('admin.entityRecords.list.titleDefault')}
        </Text>
      </Stack>
      <ManageEntitiesView
        entityTypeId={entityTypeId}
        entityTypeName={entityTypeQuery.data?.name ?? null}
        entityTypeSlug={entityTypeQuery.data?.slug ?? null}
        items={items}
        recordLabels={recordLabels}
        total={total}
        availableTags={availableTags}
        relationFieldDefs={relationFieldDefs}
        selectedTagSlugs={selectedTagSlugs}
        selectedRelationFilters={selectedRelationFilters}
        selectedStatus={selectedStatus}
        searchQuery={searchQuery}
        isFilterActive={isFilterActive}
        isLoading={isLoading}
        isError={isError}
        errorTitle={errorTitle}
        isCreating={isCreating}
        createErrorTitle={createErrorTitle}
        deleteTarget={deleteTarget}
        isDeleting={isDeleting}
        onRetry={() => {
          void refetch()
        }}
        onStatusChange={setStatus}
        onSearchChange={setSearchQuery}
        onToggleTagSlug={toggleTagSlug}
        onClearTagFilter={clearTagFilter}
        onSelectRelationFilter={setRelationFilter}
        onClearRelationFilters={clearRelationFilters}
        onCreate={createEntity}
        onRequestDelete={requestDelete}
        onCancelDelete={cancelDelete}
        onConfirmDelete={confirmDelete}
      />
    </Stack>
  )
}
