import { Link, useParams } from 'react-router-dom'
import { toEntityTypeId, useEntityType } from '@/entities/entity-type'
import { ManageEntitiesView, useManageEntitiesPage } from '@/features/manage-entities'
import { Button, Stack, Text } from '@/shared/ui'

export function EntityRecordsPage() {
  const { entityTypeId: entityTypeIdParam } = useParams()
  const entityTypeId = Number(entityTypeIdParam)

  const entityTypeQuery = useEntityType(toEntityTypeId(entityTypeId))
  const {
    items,
    total,
    isLoading,
    isError,
    errorTitle,
    refetch,
    createEntity,
    isCreating,
    createErrorTitle,
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
            Back to entity types
          </Button>
        </Link>
        <Text as="h1" variant="heading-md">
          {entityTypeQuery.data?.name ?? 'Records'}
        </Text>
      </Stack>
      <ManageEntitiesView
        entityTypeId={entityTypeId}
        entityTypeName={entityTypeQuery.data?.name ?? null}
        entityTypeSlug={entityTypeQuery.data?.slug ?? null}
        items={items}
        total={total}
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
        onCreate={createEntity}
        onRequestDelete={requestDelete}
        onCancelDelete={cancelDelete}
        onConfirmDelete={confirmDelete}
      />
    </Stack>
  )
}
