import { Link, Navigate, useParams } from 'react-router-dom'
import { toEntityTypeId, useEntityType } from '@/entities/entity-type'
import { currentUserHasCapability } from '@/entities/auth'
import { ManageFieldDefsView, useManageFieldDefsPage } from '@/features/manage-field-defs'
import { Button, Stack, Text } from '@/shared/ui'

export function FieldDefsPage() {
  const { entityTypeId: entityTypeIdParam } = useParams()
  const entityTypeId = Number(entityTypeIdParam)
  const canManageSchema = currentUserHasCapability('manage_schema')

  const entityTypeQuery = useEntityType(toEntityTypeId(entityTypeId))
  const {
    items,
    isLoading,
    isError,
    errorTitle,
    refetch,
    createFieldDef,
    isCreating,
    createErrorTitle,
    editTarget,
    requestEdit,
    cancelEdit,
    updateFieldDef,
    isUpdating,
    updateErrorTitle,
    deleteTarget,
    requestDelete,
    cancelDelete,
    confirmDelete,
    isDeleting,
  } = useManageFieldDefsPage(entityTypeId)

  if (!canManageSchema) {
    return <Navigate to="/forbidden" replace />
  }

  return (
    <Stack gap="md">
      <Stack gap="sm">
        <Link to="/entity-types">
          <Button variant="secondary" size="sm">
            Back to entity types
          </Button>
        </Link>
        <Text as="h1" variant="heading-md">
          {entityTypeQuery.data?.name ?? 'Fields'}
        </Text>
      </Stack>
      <ManageFieldDefsView
        entityTypeSlug={entityTypeQuery.data?.slug ?? null}
        canManageSchema={canManageSchema}
        items={items}
        isLoading={isLoading}
        isError={isError}
        errorTitle={errorTitle}
        isCreating={isCreating}
        createErrorTitle={createErrorTitle}
        editTarget={editTarget}
        isUpdating={isUpdating}
        updateErrorTitle={updateErrorTitle}
        deleteTarget={deleteTarget}
        isDeleting={isDeleting}
        onRetry={() => {
          void refetch()
        }}
        onCreate={createFieldDef}
        onRequestEdit={requestEdit}
        onCancelEdit={cancelEdit}
        onUpdate={updateFieldDef}
        onRequestDelete={requestDelete}
        onCancelDelete={cancelDelete}
        onConfirmDelete={confirmDelete}
      />
    </Stack>
  )
}
