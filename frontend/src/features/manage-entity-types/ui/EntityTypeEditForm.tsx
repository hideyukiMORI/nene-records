import { Controller } from 'react-hook-form'
import type { EntityType } from '@/entities/entity-type'
import { useTranslation } from '@/shared/i18n'
import { Button, Input, Stack, Text } from '@/shared/ui'
import { useEditEntityTypeForm } from '../hooks/use-create-entity-type-form'
import type { CreateEntityTypeFormValues } from '../hooks/use-create-entity-type-form'

export interface EntityTypeEditFormProps {
  entityType: EntityType
  isSubmitting: boolean
  serverErrorTitle: string | null
  onSubmit: (values: CreateEntityTypeFormValues) => Promise<void>
  onCancel: () => void
}

export function EntityTypeEditForm({
  entityType,
  isSubmitting,
  serverErrorTitle,
  onSubmit,
  onCancel,
}: EntityTypeEditFormProps) {
  const { t } = useTranslation()
  const {
    control,
    handleSubmit,
    formState: { errors },
  } = useEditEntityTypeForm({
    name: entityType.name,
    slug: entityType.slug,
    isPinned: entityType.isPinned,
  })

  return (
    <form
      key={String(entityType.id)}
      className="rounded-md border border-border bg-surface-raised p-inline-md shadow-sm"
      onSubmit={(event) => {
        void handleSubmit(async (values) => {
          await onSubmit(values)
        })(event)
      }}
    >
      <Stack gap="md">
        <Text as="h2" variant="heading-sm">
          {t('admin.entityTypes.editForm.title')}
        </Text>
        <Controller
          name="name"
          control={control}
          render={({ field }) => (
            <Input
              id="entity-type-edit-name"
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
              id="entity-type-edit-slug"
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
        <Controller
          name="isPinned"
          control={control}
          render={({ field }) => (
            <label
              htmlFor="entity-type-edit-is-pinned"
              aria-label={t('admin.entityTypes.editForm.isPinned')}
              className="flex cursor-pointer items-start gap-3"
            >
              <input
                type="checkbox"
                id="entity-type-edit-is-pinned"
                checked={field.value}
                onChange={field.onChange}
                disabled={isSubmitting}
                className="mt-0.5 h-4 w-4 shrink-0 accent-accent"
              />
              <span className="flex flex-col gap-0.5">
                <span className="text-sm font-medium text-text-primary">
                  {t('admin.entityTypes.editForm.isPinned')}
                </span>
                <span className="text-xs text-text-muted">
                  {t('admin.entityTypes.editForm.isPinnedDescription')}
                </span>
              </span>
            </label>
          )}
        />
        {serverErrorTitle !== null ? <Text muted>{serverErrorTitle}</Text> : null}
        <div className="flex items-center gap-inline-sm">
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting
              ? t('admin.entityTypes.editForm.saving')
              : t('admin.entityTypes.editForm.save')}
          </Button>
          <Button type="button" variant="secondary" disabled={isSubmitting} onClick={onCancel}>
            {t('common.actions.cancel')}
          </Button>
        </div>
      </Stack>
    </form>
  )
}
