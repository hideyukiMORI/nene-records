import type { EntityTag } from '@/entities/entity-tag'
import type { Tag } from '@/entities/tag'
import { useTranslation } from '@/shared/i18n'
import { Button, Card, ErrorState, LoadingState, Select, Stack, Text } from '@/shared/ui'

export interface ManageEntityTagsViewProps {
  attachedTags: EntityTag[]
  availableTags: Tag[]
  selectedTagId: string
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  isAttaching: boolean
  attachErrorTitle: string | null
  isDetaching: boolean
  onSelectedTagIdChange: (value: string) => void
  onRetry: () => void
  onAttach: () => Promise<void>
  onDetach: (tag: EntityTag) => Promise<void>
}

export function ManageEntityTagsView({
  attachedTags,
  availableTags,
  selectedTagId,
  isLoading,
  isError,
  errorTitle,
  isAttaching,
  attachErrorTitle,
  isDetaching,
  onSelectedTagIdChange,
  onRetry,
  onAttach,
  onDetach,
}: ManageEntityTagsViewProps) {
  const { t } = useTranslation()

  if (isLoading) {
    return <LoadingState>{t('admin.entityTags.loading')}</LoadingState>
  }

  if (isError) {
    return (
      <ErrorState
        title={t('admin.entityTags.error')}
        message={errorTitle ?? t('common.error.unknown')}
        onRetry={onRetry}
        retryLabel={t('common.actions.retry')}
      />
    )
  }

  return (
    <Stack gap="md">
      <Text as="h2" variant="heading-sm">
        {t('admin.entityTags.title')}
      </Text>
      {attachedTags.length === 0 ? (
        <Text muted>{t('admin.entityTags.noAttached')}</Text>
      ) : (
        <ul className="flex flex-col gap-stack-sm">
          {attachedTags.map((tag) => (
            <Card
              as="li"
              key={String(tag.id)}
              padding="row"
              className="flex items-center justify-between gap-inline-md"
            >
              <Stack gap="xs">
                <Text as="span" variant="heading-sm">
                  {tag.name}
                </Text>
                <Text as="span" muted>
                  {tag.slug}
                </Text>
              </Stack>
              <Button
                variant="secondary"
                size="sm"
                disabled={isDetaching}
                onClick={() => {
                  void onDetach(tag)
                }}
              >
                {t('admin.entityTags.remove')}
              </Button>
            </Card>
          ))}
        </ul>
      )}
      <Stack gap="sm">
        <Select
          id="entity-tag-select"
          label={t('admin.entityTags.addLabel')}
          disabled={isAttaching || availableTags.length === 0}
          value={selectedTagId}
          onChange={(event) => {
            onSelectedTagIdChange(event.target.value)
          }}
        >
          <option value="">
            {availableTags.length === 0
              ? t('admin.entityTags.noAvailable')
              : t('admin.entityTags.selectPlaceholder')}
          </option>
          {availableTags.map((tag) => (
            <option key={String(tag.id)} value={String(tag.id)}>
              {tag.name}
            </option>
          ))}
        </Select>
        {attachErrorTitle !== null ? <Text muted>{attachErrorTitle}</Text> : null}
        <Button
          variant="secondary"
          disabled={isAttaching || selectedTagId === ''}
          onClick={() => {
            void onAttach()
          }}
        >
          {isAttaching ? t('admin.entityTags.adding') : t('admin.entityTags.addSubmit')}
        </Button>
      </Stack>
    </Stack>
  )
}
