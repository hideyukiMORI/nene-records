import { Controller } from 'react-hook-form'
import { useTranslation } from '@/shared/i18n'
import { Button, Card, Input, SectionHeader, Stack, Text } from '@/shared/ui'
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
    <Card
      as="form"
      onSubmit={(event) => {
        void handleSubmit(async (values) => {
          await onSubmit(values)
          reset()
        })(event)
      }}
    >
      <Stack gap="md">
        {/* Panel header — muted chrome eyebrow (参考 redesign_05 .pf-panel__h) */}
        <SectionHeader>{t('admin.tags.createForm.title')}</SectionHeader>
        {/* Name と Slug は横並び（参考 redesign_05 .pf-formgrid: 1fr 1fr） */}
        <div className="grid grid-cols-1 gap-inline-md sm:grid-cols-2">
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
                placeholder={t('admin.tags.createForm.slugPlaceholder')}
                error={errors.slug?.message}
                autoComplete="off"
                disabled={isSubmitting}
                value={field.value}
                onChange={field.onChange}
                onBlur={field.onBlur}
              />
            )}
          />
        </div>
        {serverErrorTitle !== null ? <Text muted>{serverErrorTitle}</Text> : null}
        <Button
          type="submit"
          disabled={isSubmitting}
          data-testid={isSubmitting ? 'submit-in-flight' : 'submit-idle'}
        >
          {isSubmitting ? t('admin.tags.createForm.submitting') : t('admin.tags.createForm.submit')}
        </Button>
      </Stack>
    </Card>
  )
}
