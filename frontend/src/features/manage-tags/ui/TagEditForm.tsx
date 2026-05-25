import { Controller } from 'react-hook-form'
import type { Tag } from '@/entities/tag'
import { useTranslation } from '@/shared/i18n'
import { Button, Input, Stack, Text } from '@/shared/ui'
import { useEditTagForm } from '../hooks/use-create-tag-form'

export interface TagEditFormProps {
  tag: Tag
  isSubmitting: boolean
  serverErrorTitle: string | null
  onSubmit: (values: { name: string; slug: string }) => Promise<void>
  onCancel: () => void
}

export function TagEditForm({
  tag,
  isSubmitting,
  serverErrorTitle,
  onSubmit,
  onCancel,
}: TagEditFormProps) {
  const { t } = useTranslation()
  const {
    control,
    handleSubmit,
    formState: { errors },
  } = useEditTagForm({
    name: tag.name,
    slug: tag.slug,
  })

  return (
    <form
      key={String(tag.id)}
      className="rounded-md border border-border bg-surface-raised p-inline-md shadow-sm"
      onSubmit={(event) => {
        void handleSubmit(async (values) => {
          await onSubmit(values)
        })(event)
      }}
    >
      <Stack gap="md">
        <Text as="h2" variant="heading-sm">
          {t('admin.tags.editForm.title')}
        </Text>
        <Controller
          name="name"
          control={control}
          render={({ field }) => (
            <Input
              id="tag-edit-name"
              label={t('common.field.name')}
              error={errors.name?.message}
              autoComplete="off"
              disabled={isSubmitting}
              value={field.value}
              onChange={field.onChange}
              onBlur={field.onBlur}
            />
          )}
        />
        <Controller
          name="slug"
          control={control}
          render={({ field }) => (
            <Input
              id="tag-edit-slug"
              label={t('common.field.slug')}
              error={errors.slug?.message}
              autoComplete="off"
              disabled={isSubmitting}
              value={field.value}
              onChange={field.onChange}
              onBlur={field.onBlur}
            />
          )}
        />
        {serverErrorTitle !== null ? <Text muted>{serverErrorTitle}</Text> : null}
        <div className="flex items-center gap-inline-sm">
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting ? t('admin.tags.editForm.saving') : t('admin.tags.editForm.save')}
          </Button>
          <Button type="button" variant="secondary" disabled={isSubmitting} onClick={onCancel}>
            {t('common.actions.cancel')}
          </Button>
        </div>
      </Stack>
    </form>
  )
}
