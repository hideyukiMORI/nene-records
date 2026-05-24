import type { Tag } from '@/entities/tag'
import { Button, Stack, Text } from '@/shared/ui'

export interface EntityTagFilterPanelProps {
  tags: Tag[]
  selectedTagSlugs: string[]
  onToggleTagSlug: (slug: string) => void
  onClear: () => void
}

export function EntityTagFilterPanel({
  tags,
  selectedTagSlugs,
  onToggleTagSlug,
  onClear,
}: EntityTagFilterPanelProps) {
  if (tags.length === 0) {
    return null
  }

  return (
    <Stack gap="sm">
      <Stack direction="horizontal" gap="sm">
        <Text as="h2" variant="heading-sm">
          Filter by tag
        </Text>
        {selectedTagSlugs.length > 0 ? (
          <Button variant="secondary" size="sm" onClick={onClear}>
            Clear
          </Button>
        ) : null}
      </Stack>
      <div className="flex flex-wrap gap-inline-sm">
        {tags.map((tag) => {
          const isSelected = selectedTagSlugs.includes(tag.slug)

          return (
            <Button
              key={String(tag.id)}
              variant={isSelected ? 'primary' : 'secondary'}
              size="sm"
              aria-pressed={isSelected}
              onClick={() => {
                onToggleTagSlug(tag.slug)
              }}
            >
              {tag.name}
            </Button>
          )
        })}
      </div>
      {selectedTagSlugs.length > 0 ? (
        <Text muted>Showing records with any selected tag.</Text>
      ) : null}
    </Stack>
  )
}
