import { Link, useNavigate, useParams } from 'react-router-dom'
import {
  getLocalizedEntityTypeName,
  useEntityTypeBySlug,
  type EntityType,
} from '@/entities/entity-type'
import { ManageEntitiesView, useManageEntitiesPage } from '@/features/manage-entities'
import { useTranslation } from '@/shared/i18n'
import { Button, Stack, Text } from '@/shared/ui'
import { IconChevronLeft } from '@/shared/ui/icons/Icons'

export function EntityRecordsPage() {
  const { t } = useTranslation()
  const { entityTypeSlug = '' } = useParams()
  const entityTypeQuery = useEntityTypeBySlug(entityTypeSlug)

  if (entityTypeQuery.isPending) {
    return <Text muted>{t('admin.entityRecords.list.titleDefault')}</Text>
  }
  if (entityTypeQuery.isError) {
    return <Text muted>{entityTypeQuery.error.title}</Text>
  }

  return <EntityRecordsContent entityType={entityTypeQuery.data} />
}

function EntityRecordsContent({ entityType }: { entityType: EntityType }) {
  const { t, locale } = useTranslation()
  const navigate = useNavigate()

  const {
    items,
    recordLabels,
    total,
    page,
    totalPages,
    prevPage,
    nextPage,
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
    toggleTagSlug,
    clearTagFilter,
    setRelationFilter,
    clearRelationFilters,
    deleteTarget,
    requestDelete,
    cancelDelete,
    confirmDelete,
    isDeleting,
  } = useManageEntitiesPage(Number(entityType.id))

  const handleCreate = async () => {
    const newEntity = await createEntity()
    void navigate(`/${entityType.slug}/${String(newEntity.id)}`)
  }

  const localizedName = getLocalizedEntityTypeName(entityType, locale)

  return (
    <Stack gap="md">
      <Stack gap="xs">
        {/* ── Breadcrumb ── */}
        <nav aria-label="breadcrumb">
          <Link
            to="/entity-types"
            className="inline-flex items-center gap-1 text-xs text-text-muted transition-colors duration-fast hover:text-text-primary"
          >
            <IconChevronLeft size={12} />
            {t('admin.entityRecords.backToTypes')}
          </Link>
        </nav>
        {/* ── Page header ── */}
        <div className="flex items-center justify-between gap-4">
          <Text as="h1" variant="heading-md">
            {localizedName}
          </Text>
          <Button
            disabled={isCreating}
            onClick={() => {
              void handleCreate()
            }}
          >
            {isCreating
              ? t('admin.entityRecords.create.submitting')
              : t('admin.entityRecords.create.newButton')}
          </Button>
        </div>
      </Stack>
      <ManageEntitiesView
        entityTypeId={Number(entityType.id)}
        entityTypeSlug={entityType.slug}
        entityTypeName={localizedName}
        items={items}
        recordLabels={recordLabels}
        total={total}
        page={page}
        totalPages={totalPages}
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
        deleteTarget={deleteTarget}
        isDeleting={isDeleting}
        onRetry={() => {
          void refetch()
        }}
        onStatusChange={setStatus}
        onSearchChange={setSearchQuery}
        onPrevPage={prevPage}
        onNextPage={nextPage}
        onToggleTagSlug={toggleTagSlug}
        onClearTagFilter={clearTagFilter}
        onSelectRelationFilter={setRelationFilter}
        onClearRelationFilters={clearRelationFilters}
        onRequestDelete={requestDelete}
        onCancelDelete={cancelDelete}
        onConfirmDelete={confirmDelete}
      />
    </Stack>
  )
}
