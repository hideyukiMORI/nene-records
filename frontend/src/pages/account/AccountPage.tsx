import { Navigate } from 'react-router-dom'
import { useAccount } from '@/entities/account'
import { currentUserIsAdmin } from '@/entities/auth'
import { useTranslation } from '@/shared/i18n'
import { formatBytes } from '@/shared/lib/format-bytes'
import { Card, PageHeader, Stack, Text } from '@/shared/ui'

export function AccountPage() {
  const { t } = useTranslation()
  const isAdmin = currentUserIsAdmin()
  const { data, isLoading, isError } = useAccount()

  // Account is an admin-only surface (defence in depth: nav hides it, the API
  // requires ManageAccount, and the route is behind RequireAuth).
  if (!isAdmin) {
    return <Navigate to="/forbidden" replace />
  }

  const numberLimit = (value: number | null): string =>
    value === null ? t('admin.account.unlimited') : value.toLocaleString()
  const storageLimit = (value: number | null): string =>
    value === null ? t('admin.account.unlimited') : formatBytes(value)

  return (
    <Stack gap="lg">
      <PageHeader title={t('admin.account.title')} description={t('admin.account.description')} />

      {isLoading ? <Text muted>{t('admin.account.loading')}</Text> : null}
      {isError ? <Text muted>{t('admin.account.error')}</Text> : null}

      {data ? (
        <>
          <Card padding="none" className="p-4">
            <Stack gap="sm">
              <Text as="h2" variant="heading-sm">
                {t('admin.account.planHeading')}
              </Text>
              <Text>{t('admin.account.currentPlan', { plan: data.plan })}</Text>
              <Text muted>{t('admin.account.upgradeHint')}</Text>
            </Stack>
          </Card>

          <Card padding="none" className="p-4">
            <Stack gap="sm">
              <Text as="h2" variant="heading-sm">
                {t('admin.account.usageHeading')}
              </Text>
              <Text>
                {t('admin.account.recordsUsage', {
                  used: data.recordsUsed.toLocaleString(),
                  limit: numberLimit(data.entitlements.maxRecords),
                })}
              </Text>
            </Stack>
          </Card>

          <Card padding="none" className="p-4">
            <Stack gap="sm">
              <Text as="h2" variant="heading-sm">
                {t('admin.account.limitsHeading')}
              </Text>
              <dl className="grid grid-cols-2 gap-2 text-body">
                <dt className="text-text-muted">{t('admin.account.customDomainLabel')}</dt>
                <dd>
                  {data.entitlements.customDomainAllowed
                    ? t('admin.account.allowed')
                    : t('admin.account.notAllowed')}
                </dd>
                <dt className="text-text-muted">{t('admin.account.maxRecordsLabel')}</dt>
                <dd>{numberLimit(data.entitlements.maxRecords)}</dd>
                <dt className="text-text-muted">{t('admin.account.maxStorageLabel')}</dt>
                <dd>{storageLimit(data.entitlements.maxStorageBytes)}</dd>
                <dt className="text-text-muted">{t('admin.account.maxAdminUsersLabel')}</dt>
                <dd>{numberLimit(data.entitlements.maxAdminUsers)}</dd>
              </dl>
            </Stack>
          </Card>
        </>
      ) : null}
    </Stack>
  )
}
