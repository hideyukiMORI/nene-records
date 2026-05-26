import { Link, Navigate, useParams } from 'react-router-dom'
import {
  getLocalizedEntityTypeName,
  useEntityTypeBySlug,
  type EntityType,
} from '@/entities/entity-type'
import { currentUserHasCapability } from '@/entities/auth'
import { ManageFieldDefsView, useManageFieldDefsPage } from '@/features/manage-field-defs'
import { useTranslation } from '@/shared/i18n'
import { Button, Stack, Text } from '@/shared/ui'

export function FieldDefsPage() {
  const { t } = useTranslation()
  const { entityTypeSlug = '' } = useParams()
  const canManageSchema = currentUserHasCapability('manage_schema')
  const entityTypeQuery = useEntityTypeBySlug(entityTypeSlug)

  if (!canManageSchema) {
    return <Navigate to="/forbidden" replace />
  }

  if (entityTypeQuery.isPending) {
    return <Text muted>{t('admin.fieldDefs.pageTitle')}</Text>
  }
  if (entityTypeQuery.isError) {
    return <Text muted>{entityTypeQuery.error.title}</Text>
  }

  return <FieldDefsContent entityType={entityTypeQuery.data} />
}

function FieldDefsContent({ entityType }: { entityType: EntityType }) {
  const { t, locale } = useTranslation()
  const canManageSchema = currentUserHasCapability('manage_schema')
  const entityTypeId = Number(entityType.id)

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

  return (
    <Stack gap="md">
      <Stack gap="sm">
        <Link to="/admin/entity-types">
          <Button variant="secondary" size="sm">
            {t('admin.entityTypes.actions.backToTypes')}
          </Button>
        </Link>
        <Text as="h1" variant="heading-md">
          {getLocalizedEntityTypeName(entityType, locale)}
        </Text>
      </Stack>
      <ManageFieldDefsView
        entityTypeSlug={entityType.slug}
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
