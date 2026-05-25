import { useTranslation } from '@/shared/i18n'
import { Button, Stack, Text } from '@/shared/ui'

export interface EntityCreatePanelProps {
  isSubmitting: boolean
  serverErrorTitle: string | null
  onCreate: () => Promise<void>
}

export function EntityCreatePanel({
  isSubmitting,
  serverErrorTitle,
  onCreate,
}: EntityCreatePanelProps) {
  const { t } = useTranslation()

  return (
    <Stack gap="sm">
      <Text as="h2" variant="heading-sm">
        {t('admin.entityRecords.create.title')}
      </Text>
      <Text muted>{t('admin.entityRecords.create.description')}</Text>
      {serverErrorTitle !== null ? <Text muted>{serverErrorTitle}</Text> : null}
      <div>
        <Button
          disabled={isSubmitting}
          onClick={() => {
            void onCreate()
          }}
        >
          {isSubmitting
            ? t('admin.entityRecords.create.submitting')
            : t('admin.entityRecords.create.submit')}
        </Button>
      </div>
    </Stack>
  )
}
