import { Controller } from 'react-hook-form'
import { useTranslation } from '@/shared/i18n'
import { Button, Input, Stack, Text } from '@/shared/ui'
import { useCreateTagForm } from '../hooks/use-create-tag-form'

export interface TagCreateFormProps {
  isSubmitting: boolean
  serverErrorTitle: string | null
  onSubmit: (values: { name: string; slug: string }) => Promise<void>
}

export function TagCreateForm({ isSubmitting, serverErrorTitle, onSubmit }: TagCreateFormProps) {
  const { t } = useTranslation()
  const {
    control,
    handleSubmit,
    reset,
    formState: { errors },
  } = useCreateTagForm()

  return (
    <form
      className="rounded-md border border-border bg-surface-raised p-inline-md shadow-sm"
      onSubmit={(event) => {
        void handleSubmit(async (values) => {
          await onSubmit(values)
          reset()
        })(event)
      }}
    >
      <Stack gap="md">
        <Text as="h2" variant="heading-sm">
          {t('admin.tags.createForm.title')}
        </Text>
        <Controller
          name="name"
          control={control}
          render={({ field }) => (
            <Input
              id="tag-name"
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
              id="tag-slug"
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
        <Button type="submit" disabled={isSubmitting}>
          {isSubmitting ? t('admin.tags.createForm.submitting') : t('admin.tags.createForm.submit')}
        </Button>
      </Stack>
    </form>
  )
}
