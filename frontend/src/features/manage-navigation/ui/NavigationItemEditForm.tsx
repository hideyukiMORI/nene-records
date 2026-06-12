import { useCallback } from 'react'
import { useForm } from 'react-hook-form'
import type { NavigationItem } from '@/entities/navigation-item'
import { useTranslation } from '@/shared/i18n'
import { Button, Card, Input, Stack, Text } from '@/shared/ui'
import type { NavigationItemFormValues } from '../hooks/use-manage-navigation-page'

export interface NavigationItemEditFormProps {
  item: NavigationItem
  isSubmitting: boolean
  serverErrorTitle: string | null
  onSubmit: (values: NavigationItemFormValues) => Promise<void>
  onCancel: () => void
}

export function NavigationItemEditForm({
  item,
  isSubmitting,
  serverErrorTitle,
  onSubmit,
  onCancel,
}: NavigationItemEditFormProps) {
  const { t } = useTranslation()
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<NavigationItemFormValues>({
    defaultValues: { label: item.label, url: item.url, displayOrder: item.displayOrder },
  })

  const submit = useCallback(
    async (values: NavigationItemFormValues) => {
      await onSubmit(values)
    },
    [onSubmit],
  )

  return (
    <Card
      as="form"
      onSubmit={(event) => {
        void handleSubmit(submit)(event)
      }}
    >
      <Stack gap="md">
        <Text as="h2" variant="heading-sm">
          {t('common.actions.edit')}: {item.label}
        </Text>
        <Input
          id={`nav-edit-label-${String(item.id)}`}
          label={t('admin.navigation.label')}
          placeholder={t('admin.navigation.form.labelPlaceholder')}
          error={errors.label?.message}
          autoComplete="off"
          disabled={isSubmitting}
          {...register('label', { required: t('admin.navigation.label') + ' is required.' })}
        />
        <Input
          id={`nav-edit-url-${String(item.id)}`}
          label={t('admin.navigation.url')}
          placeholder={t('admin.navigation.form.urlPlaceholder')}
          error={errors.url?.message}
          autoComplete="off"
          disabled={isSubmitting}
          {...register('url', { required: t('admin.navigation.url') + ' is required.' })}
        />
        <Input
          id={`nav-edit-order-${String(item.id)}`}
          label={t('admin.navigation.displayOrder')}
          type="number"
          error={errors.displayOrder?.message}
          disabled={isSubmitting}
          {...register('displayOrder', { valueAsNumber: true })}
        />
        {serverErrorTitle !== null ? <Text muted>{serverErrorTitle}</Text> : null}
        <div className="flex gap-inline-sm">
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting ? t('admin.navigation.saving') : t('admin.navigation.save')}
          </Button>
          <Button type="button" variant="secondary" disabled={isSubmitting} onClick={onCancel}>
            {t('admin.navigation.cancel')}
          </Button>
        </div>
      </Stack>
    </Card>
  )
}
