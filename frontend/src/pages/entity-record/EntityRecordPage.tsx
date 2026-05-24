import { Link, useParams } from 'react-router-dom'
import { toEntityTypeId, useEntityType } from '@/entities/entity-type'
import {
  EditEntityTextFieldsView,
  useEditEntityTextFieldsPage,
} from '@/features/edit-entity-text-fields'
import { Button, Stack, Text } from '@/shared/ui'

export function EntityRecordPage() {
  const { entityTypeId: entityTypeIdParam, entityId: entityIdParam } = useParams()
  const entityTypeId = Number(entityTypeIdParam)
  const entityId = Number(entityIdParam)

  const entityTypeQuery = useEntityType(toEntityTypeId(entityTypeId))
  const {
    entity,
    textFieldDefs,
    initialValues,
    isLoading,
    isError,
    errorTitle,
    refetch,
    saveTextFields,
    isSaving,
    saveErrorTitle,
  } = useEditEntityTextFieldsPage(entityTypeId, entityId)

  return (
    <Stack gap="md">
      <Stack gap="sm">
        <Link to={`/entity-types/${String(entityTypeId)}/entities`}>
          <Button variant="secondary" size="sm">
            Back to records
          </Button>
        </Link>
        <Text as="h1" variant="heading-md">
          {entityTypeQuery.data?.name ?? 'Record'}
        </Text>
      </Stack>
      <EditEntityTextFieldsView
        entity={entity}
        textFieldDefs={textFieldDefs}
        initialValues={initialValues}
        isLoading={isLoading}
        isError={isError}
        errorTitle={errorTitle}
        isSaving={isSaving}
        saveErrorTitle={saveErrorTitle}
        onRetry={() => {
          void refetch()
        }}
        onSave={saveTextFields}
      />
    </Stack>
  )
}
