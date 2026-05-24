import type { EntityTag } from '@/entities/entity-tag'
import type { Tag } from '@/entities/tag'
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
  if (isLoading) {
    return <Text muted>Loading tags…</Text>
  }

  if (isError) {
    return (
      <Stack gap="sm">
        <Text variant="heading-sm">Could not load tags</Text>
        <Text muted>{errorTitle ?? 'Unknown error'}</Text>
        <Button variant="secondary" onClick={onRetry}>
          Retry
        </Button>
      </Stack>
    )
  }

  return (
    <Stack gap="md">
      <Text as="h2" variant="heading-sm">
        Tags
      </Text>
      {attachedTags.length === 0 ? (
        <Text muted>No tags attached yet.</Text>
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
                Remove
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
            Add tag
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
              {availableTags.length === 0 ? 'No tags available' : 'Select tag…'}
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
          {isAttaching ? 'Adding…' : 'Add tag'}
        </Button>
      </Stack>
    </Stack>
  )
}
