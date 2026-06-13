import { Navigate } from 'react-router-dom'
import { currentUserHasCapability } from '@/entities/auth'
import { ManageWidgetsView, useManageWidgetsPage } from '@/features/manage-widgets'
import { useTranslation } from '@/shared/i18n'
import { Stack, Text } from '@/shared/ui'

export function WidgetsPage() {
  const { t } = useTranslation()
  const canManageSettings = currentUserHasCapability('manage_settings')
  const page = useManageWidgetsPage()

  if (!canManageSettings) {
    return <Navigate to="/forbidden" replace />
  }

  return (
    <Stack gap="md">
      <Text as="h1" variant="heading-md">
        {t('admin.widgets.pageTitle')}
      </Text>
      <Text muted>{t('admin.widgets.pageDescription')}</Text>
      <ManageWidgetsView
        widgets={page.widgets}
        entityTypes={page.entityTypes}
        menus={page.menus}
        form={page.form}
        editId={page.editId}
        isSubmitting={page.isSubmitting}
        setField={page.setField}
        resetForm={page.resetForm}
        addToRegion={page.addToRegion}
        editWidget={page.editWidget}
        submit={page.submit}
        remove={page.remove}
      />
    </Stack>
  )
}
