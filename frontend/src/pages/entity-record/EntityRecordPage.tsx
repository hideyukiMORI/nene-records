import { Link, useParams } from 'react-router-dom'
import type { Entity } from '@/entities/entity'
import {
  getLocalizedEntityTypeName,
  useEntityTypeBySlug,
  type EntityType,
} from '@/entities/entity-type'
import {
  EditEntityTextFieldsView,
  useEditEntityTextFieldsPage,
} from '@/features/edit-entity-text-fields'
import {
  EntityRevisionsPanel,
  EntitySeoPanel,
  EntityStatusPanel,
  useEntityRevisionsPanel,
  useEntitySeoPanel,
} from '@/features/manage-entity-status'
import { ManageEntityTagsView, useManageEntityTagsPage } from '@/features/manage-entity-tags'
import {
  ManageEntityRelationsView,
  useManageEntityRelationsView,
} from '@/features/manage-entity-relations'
import { InverseEntityRelationsView } from '@/features/inverse-entity-relations'
import { useTranslation } from '@/shared/i18n'
import { Button, Stack, Text } from '@/shared/ui'

export function EntityRecordPage() {
  const { t } = useTranslation()
  const { entityTypeSlug = '', entityId: entityIdParam } = useParams()
  const entityId = Number(entityIdParam)

  const entityTypeQuery = useEntityTypeBySlug(entityTypeSlug)

  if (entityTypeQuery.isPending) {
    return <Text muted>{t('admin.entityRecords.list.titleDefault')}</Text>
  }
  if (entityTypeQuery.isError) {
    return <Text muted>{entityTypeQuery.error.title}</Text>
  }

  return <EntityRecordContent entityType={entityTypeQuery.data} entityId={entityId} />
}

// ── Thin wrapper so useEntitySeoPanel runs only after entity loads ────────────

function EntitySeoPanelSection({ entity }: { entity: Entity }) {
  const seoPanel = useEntitySeoPanel(entity)
  return <EntitySeoPanel {...seoPanel} />
}

// ── Main content ─────────────────────────────────────────────────────────────

function EntityRecordContent({
  entityType,
  entityId,
}: {
  entityType: EntityType
  entityId: number
}) {
  const { t, locale } = useTranslation()
  const entityTypeId = Number(entityType.id)

  const {
    entity,
    textFieldDefs,
    initialValues,
    selectedLocale,
    availableLocales,
    setLocale,
    isLoading,
    isError,
    errorTitle,
    refetch,
    saveTextFields,
    isSaving,
    saveErrorTitle,
  } = useEditEntityTextFieldsPage(entityTypeId, entityId)

  const revisionsPanel = useEntityRevisionsPanel(entityId)
  const entityRelations = useManageEntityRelationsView(entityTypeId)

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
        <Link to={`/admin/${entityType.slug}`}>
          <Button variant="secondary" size="sm">
            {t('admin.entityRecord.backToRecords')}
          </Button>
        </Link>
        <Text as="h1" variant="heading-md">
          {getLocalizedEntityTypeName(entityType, locale)}
        </Text>
      </Stack>
      {entity !== null && <EntityStatusPanel entity={entity} entityTypeSlug={entityType.slug} />}
      <EditEntityTextFieldsView
        entity={entity}
        textFieldDefs={textFieldDefs}
        initialValues={initialValues}
        selectedLocale={selectedLocale}
        availableLocales={availableLocales}
        isLoading={isLoading}
        isError={isError}
        errorTitle={errorTitle}
        isSaving={isSaving}
        saveErrorTitle={saveErrorTitle}
        onLocaleChange={setLocale}
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
      <ManageEntityRelationsView entityId={entityId} {...entityRelations} />
      <InverseEntityRelationsView entityId={entityId} entityTypeId={entityTypeId} />
      {entity !== null && <EntitySeoPanelSection entity={entity} />}
      <EntityRevisionsPanel {...revisionsPanel} />
    </Stack>
  )
}
