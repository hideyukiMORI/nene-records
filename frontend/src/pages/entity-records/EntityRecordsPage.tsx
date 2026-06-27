import { Link, useNavigate, useParams } from 'react-router-dom'
import {
  getLocalizedEntityTypeName,
  useEntityTypeBySlug,
  type EntityType,
} from '@/entities/entity-type'
import { ManageEntitiesView, useManageEntitiesPage } from '@/features/manage-entities'
import { useTranslation } from '@/shared/i18n'
import { Button, PageHeader, Stack, Text } from '@/shared/ui'
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
    recordBodyMap,
    total,
    page,
    totalPages,
    pageSize,
    setPageSize,
    pageSizeOptions,
    viewMode,
    setViewMode,
    directoryRecords,
    directoryTruncated,
    directoryIsLoading,
    directoryIsError,
    directoryErrorTitle,
    sortKey,
    sortOrder,
    setSort,
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

  const handleCreate = async (permalinkPrefix?: string) => {
    const newEntity = await createEntity()
    const query =
      permalinkPrefix !== undefined && permalinkPrefix !== ''
        ? `?permalinkPrefix=${encodeURIComponent(permalinkPrefix)}`
        : ''
    void navigate(`/admin/${entityType.slug}/${String(newEntity.id)}${query}`)
  }

  const localizedName = getLocalizedEntityTypeName(entityType, locale)

  return (
    <Stack gap="md">
      <Stack gap="xs">
        {/* ── Breadcrumb ── */}
        <nav aria-label="breadcrumb">
          <Link
            to="/admin/entity-types"
            className="inline-flex items-center gap-1 text-xs text-text-muted transition-colors duration-fast hover:text-text-primary"
          >
            <IconChevronLeft size={12} />
            {t('admin.entityRecords.backToTypes')}
          </Link>
        </nav>
        {/* ── Page header ── */}
        <PageHeader
          title={localizedName}
          actions={
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
          }
        />
      </Stack>
      <ManageEntitiesView
        entityTypeId={Number(entityType.id)}
        entityTypeSlug={entityType.slug}
        entityTypeName={localizedName}
        items={items}
        recordLabels={recordLabels}
        recordBodyMap={recordBodyMap}
        total={total}
        page={page}
        totalPages={totalPages}
        sortKey={sortKey}
        sortOrder={sortOrder}
        onSortChange={setSort}
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
        viewMode={viewMode}
        onViewModeChange={setViewMode}
        pageSize={pageSize}
        pageSizeOptions={pageSizeOptions}
        onPageSizeChange={setPageSize}
        directoryItems={directoryRecords}
        directoryTruncated={directoryTruncated}
        directoryIsLoading={directoryIsLoading}
        directoryIsError={directoryIsError}
        directoryErrorTitle={directoryErrorTitle}
        onCreateHere={(permalinkPrefix) => {
          void handleCreate(permalinkPrefix)
        }}
      />
    </Stack>
  )
}
