import type { EntityTag } from '@/entities/entity-tag'
import type { Tag } from '@/entities/tag'
import { useTranslation } from '@/shared/i18n'
import { Button, Stack, Text } from '@/shared/ui'

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
    return <Text muted>{t('admin.entityTags.loading')}</Text>
  }

  if (isError) {
    return (
      <Stack gap="sm">
        <Text variant="heading-sm">{t('admin.entityTags.error')}</Text>
        <Text muted>{errorTitle ?? t('common.error.unknown')}</Text>
        <Button variant="secondary" onClick={onRetry}>
          {t('common.actions.retry')}
        </Button>
      </Stack>
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
            <li
              key={String(tag.id)}
              className="flex items-center justify-between gap-inline-md rounded-md border border-border bg-surface-raised px-inline-md py-stack-sm shadow-sm"
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
            </li>
          ))}
        </ul>
      )}
      <Stack gap="sm">
        <div className="flex flex-col gap-stack-xs">
          <label
            htmlFor="entity-tag-select"
            className="font-sans text-body font-medium text-text-primary"
          >
            {t('admin.entityTags.addLabel')}
          </label>
          <select
            id="entity-tag-select"
            disabled={isAttaching || availableTags.length === 0}
            value={selectedTagId}
            onChange={(event) => {
              onSelectedTagIdChange(event.target.value)
            }}
            className="rounded-md border border-border bg-surface-raised px-inline-md py-stack-sm font-sans text-body text-text-primary shadow-sm focus-visible:outline-none focus-visible:shadow-focus disabled:cursor-not-allowed disabled:opacity-50"
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
          </select>
        </div>
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
