import { useCallback } from 'react'
import { useForm } from 'react-hook-form'
import { useTranslation } from '@/shared/i18n'
import { Button, Input, Stack, Text } from '@/shared/ui'
import type { NavigationItemFormValues } from '../hooks/use-manage-navigation-page'

export interface NavigationItemCreateFormProps {
  isSubmitting: boolean
  serverErrorTitle: string | null
  onSubmit: (values: NavigationItemFormValues) => Promise<void>
}

export function NavigationItemCreateForm({
  isSubmitting,
  serverErrorTitle,
  onSubmit,
}: NavigationItemCreateFormProps) {
  const { t } = useTranslation()
  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<NavigationItemFormValues>({
    defaultValues: { label: '', url: '', displayOrder: 0 },
  })

  const submit = useCallback(
    async (values: NavigationItemFormValues) => {
      await onSubmit(values)
      reset()
    },
    [onSubmit, reset],
  )

  return (
    <form
      className="rounded-md border border-border bg-surface-raised p-inline-md shadow-sm"
      onSubmit={(event) => {
        void handleSubmit(submit)(event)
      }}
    >
      <Stack gap="md">
        <Text as="h2" variant="heading-sm">
          {t('admin.navigation.add')}
        </Text>
        <Input
          id="nav-create-label"
          label={t('admin.navigation.label')}
          placeholder={t('admin.navigation.form.labelPlaceholder')}
          error={errors.label?.message}
          autoComplete="off"
          disabled={isSubmitting}
          {...register('label', { required: t('admin.navigation.label') + ' is required.' })}
        />
        <Input
          id="nav-create-url"
          label={t('admin.navigation.url')}
          placeholder={t('admin.navigation.form.urlPlaceholder')}
          error={errors.url?.message}
          autoComplete="off"
          disabled={isSubmitting}
          {...register('url', { required: t('admin.navigation.url') + ' is required.' })}
        />
        <Input
          id="nav-create-order"
          label={t('admin.navigation.displayOrder')}
          type="number"
          error={errors.displayOrder?.message}
          disabled={isSubmitting}
          {...register('displayOrder', { valueAsNumber: true })}
        />
        {serverErrorTitle !== null ? <Text muted>{serverErrorTitle}</Text> : null}
        <Button type="submit" disabled={isSubmitting}>
          {isSubmitting ? t('admin.navigation.saving') : t('admin.navigation.save')}
        </Button>
      </Stack>
    </form>
  )
}
