import type { Tag } from '@/entities/tag'
import { useTranslation } from '@/shared/i18n'
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
  const { t } = useTranslation()

  if (tags.length === 0) {
    return null
  }

  return (
    <Stack gap="sm">
      <Stack direction="horizontal" gap="sm">
        <Text as="h2" variant="heading-sm">
          {t('admin.entityRecords.tagFilter.label')}
        </Text>
        {selectedTagSlugs.length > 0 ? (
          <Button variant="secondary" size="sm" onClick={onClear}>
            {t('admin.entityRecords.tagFilter.clear')}
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
        <Text muted>{t('admin.entityRecords.tagFilter.hint')}</Text>
      ) : null}
    </Stack>
  )
}
