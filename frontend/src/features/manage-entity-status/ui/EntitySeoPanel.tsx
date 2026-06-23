import { useTranslation } from '@/shared/i18n'
import { Button, Input, Stack, Text, Textarea } from '@/shared/ui'

interface EntitySeoPanelProps {
  metaTitle: string
  metaDescription: string
  isPending: boolean
  onMetaTitleChange: (value: string) => void
  onMetaDescriptionChange: (value: string) => void
  onSave: () => void
}

export function EntitySeoPanel({
  metaTitle,
  metaDescription,
  isPending,
  onMetaTitleChange,
  onMetaDescriptionChange,
  onSave,
}: EntitySeoPanelProps) {
  const { t } = useTranslation()

  return (
    <section className="rounded-xl border border-border bg-surface p-4">
      <Stack gap="md">
        <Text as="h2" variant="heading-sm">
          {t('admin.entitySeo.title')}
        </Text>

        <Input
          id="entity-meta-title"
          label={t('admin.entitySeo.metaTitle')}
          value={metaTitle}
          onChange={(e) => {
            onMetaTitleChange(e.target.value)
          }}
          placeholder={t('admin.entitySeo.metaTitle.placeholder')}
          maxLength={255}
        />

        <Stack gap="xs">
          <Textarea
            id="entity-meta-description"
            label={t('admin.entitySeo.metaDescription')}
            value={metaDescription}
            onChange={(e) => {
              onMetaDescriptionChange(e.target.value)
            }}
            placeholder={t('admin.entitySeo.metaDescription.placeholder')}
            rows={3}
          />
          <Text as="p" muted variant="caption">
            {t('admin.entitySeo.metaDescription.help')}
          </Text>
        </Stack>

        <div className="flex items-center gap-inline-sm">
          <Button variant="secondary" size="sm" disabled={isPending} onClick={onSave}>
            {isPending ? t('admin.entitySeo.saving') : t('admin.entitySeo.save')}
          </Button>
        </div>
      </Stack>
    </section>
  )
}
