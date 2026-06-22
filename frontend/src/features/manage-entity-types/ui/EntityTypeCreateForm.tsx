import { Controller } from 'react-hook-form'
import { type MessageKey, useTranslation } from '@/shared/i18n'
import { Button, Card, Input, Select, Stack, Text } from '@/shared/ui'
import type {
  CreateEntityTypeFormValues,
  EntityTypeStarter,
} from '../hooks/use-create-entity-type-form'
import { ENTITY_TYPE_STARTERS, useCreateEntityTypeForm } from '../hooks/use-create-entity-type-form'

const STARTER_LABEL_KEY: Record<EntityTypeStarter, MessageKey> = {
  blank: 'admin.entityTypes.starter.blank',
  article: 'admin.entityTypes.starter.article',
  rich_page: 'admin.entityTypes.starter.richPage',
}

export interface EntityTypeCreateFormProps {
  isSubmitting: boolean
  serverErrorTitle: string | null
  onSubmit: (values: CreateEntityTypeFormValues) => Promise<void>
}

export function EntityTypeCreateForm({
  isSubmitting,
  serverErrorTitle,
  onSubmit,
}: EntityTypeCreateFormProps) {
  const { t } = useTranslation()
  const {
    control,
    handleSubmit,
    reset,
    formState: { errors },
  } = useCreateEntityTypeForm()

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
        <Text as="h2" variant="heading-sm">
          {t('admin.entityTypes.createForm.title')}
        </Text>
        <Controller
          name="name"
          control={control}
          render={({ field }) => (
            <Input
              id="entity-type-name"
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
              id="entity-type-slug"
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
          name="starter"
          control={control}
          render={({ field }) => (
            <Select
              id="entity-type-starter"
              label={t('admin.entityTypes.createForm.starterLabel')}
              disabled={isSubmitting}
              value={field.value}
              onChange={field.onChange}
              onBlur={field.onBlur}
            >
              {ENTITY_TYPE_STARTERS.map((starter) => (
                <option key={starter} value={starter}>
                  {t(STARTER_LABEL_KEY[starter])}
                </option>
              ))}
            </Select>
          )}
        />
        <Text muted variant="caption">
          {t('admin.entityTypes.createForm.starterHelp')}
        </Text>
        {serverErrorTitle !== null ? <Text muted>{serverErrorTitle}</Text> : null}
        <Button
          type="submit"
          disabled={isSubmitting}
          data-testid={isSubmitting ? 'submit-in-flight' : 'submit-idle'}
        >
          {isSubmitting
            ? t('admin.entityTypes.createForm.submitting')
            : t('admin.entityTypes.createForm.submit')}
        </Button>
      </Stack>
    </Card>
  )
}
