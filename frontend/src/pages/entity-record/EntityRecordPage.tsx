import { Link, useParams } from 'react-router-dom'
import { toEntityTypeId, useEntityType } from '@/entities/entity-type'
import {
  EditEntityTextFieldsView,
  useEditEntityTextFieldsPage,
} from '@/features/edit-entity-text-fields'
import { ManageEntityTagsView, useManageEntityTagsPage } from '@/features/manage-entity-tags'
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

  const {
    attachedTags,
    availableTags,
    selectedTagId,
    setSelectedTagId,
    isLoading: isTagsLoading,
    isError: isTagsError,
    errorTitle: tagsErrorTitle,
    isAttaching,
    attachErrorTitle,
    isDetaching,
    attachTag,
    detachTag,
    refetch: refetchTags,
  } = useManageEntityTagsPage(entityId)

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
      <ManageEntityTagsView
        attachedTags={attachedTags}
        availableTags={availableTags}
        selectedTagId={selectedTagId}
        isLoading={isTagsLoading}
        isError={isTagsError}
        errorTitle={tagsErrorTitle}
        isAttaching={isAttaching}
        attachErrorTitle={attachErrorTitle}
        isDetaching={isDetaching}
        onSelectedTagIdChange={setSelectedTagId}
        onRetry={() => {
          void refetchTags()
        }}
        onAttach={attachTag}
        onDetach={detachTag}
      />
    </Stack>
  )
}
