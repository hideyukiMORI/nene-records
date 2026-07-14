import { useTranslation } from '@/shared/i18n'
import { Button, Card, PageHeader, Select, Stack, Text } from '@/shared/ui'
import type { DataMigrationPageState } from '../hooks/useDataMigrationPage'

export function DataMigrationView({
  migrationStatus,
  organizations,
  isStatusLoading,
  isStatusError,
  isAssigning,
  targetOrgId,
  onTargetOrgIdChange,
  onAssign,
}: DataMigrationPageState) {
  const { t } = useTranslation()
  const hasUnassigned = (migrationStatus?.total ?? 0) > 0

  return (
    <Stack gap="lg">
      <PageHeader
        title={t('admin.dataMigration.pageTitle')}
        description={t('admin.dataMigration.pageDescription')}
      />

      {/* Status panel */}
      <Card padding="none" className="p-6">
        <Text as="h2" variant="heading-sm">
          {t('admin.dataMigration.unassignedTitle')}
        </Text>

        {isStatusLoading && (
          <Text muted className="mt-3">
            {t('admin.dataMigration.loading')}
          </Text>
        )}
        {isStatusError && (
          <Text muted className="mt-3 text-danger">
            {t('admin.dataMigration.statusError')}
          </Text>
        )}

        {migrationStatus !== undefined && (
          <>
            <div className="mt-3 mb-4">
              {hasUnassigned ? (
                <div className="rounded-md bg-warning-weak border border-warning px-4 py-3">
                  <Text className="font-semibold text-warning">
                    {t(
                      migrationStatus.total === 1
                        ? 'admin.dataMigration.unassignedCount.one'
                        : 'admin.dataMigration.unassignedCount.other',
                      { count: migrationStatus.total.toLocaleString() },
                    )}
                  </Text>
                </div>
              ) : (
                <div className="rounded-md bg-success-weak border border-success px-4 py-3">
                  <Text className="font-semibold text-success">
                    {t('admin.dataMigration.allAssigned')}
                  </Text>
                </div>
              )}
            </div>

            {hasUnassigned && (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b border-border">
                      <th className="pb-2 text-left font-medium text-text-muted">
                        {t('admin.dataMigration.table.name')}
                      </th>
                      <th className="pb-2 text-right font-medium text-text-muted">
                        {t('admin.dataMigration.table.rows')}
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    {Object.entries(migrationStatus.tables)
                      .filter(([, count]) => count > 0)
                      .map(([table, count]) => (
                        <tr key={table} className="border-b border-border last:border-0">
                          <td className="py-2 font-mono text-text-primary">{table}</td>
                          <td className="py-2 text-right tabular-nums text-text-primary">
                            {count.toLocaleString()}
                          </td>
                        </tr>
                      ))}
                  </tbody>
                </table>
              </div>
            )}
          </>
        )}
      </Card>

      {/* Assign panel */}
      {hasUnassigned && (
        <Card padding="none" className="p-6">
          <Text as="h2" variant="heading-sm">
            {t('admin.dataMigration.assignTitle')}
          </Text>
          <Text muted className="mt-1">
            {t('admin.dataMigration.assignDesc')}
          </Text>

          <Stack gap="md" className="mt-4">
            <div>
              <label
                htmlFor="target-org"
                className="mb-1 block text-sm font-medium text-text-primary"
              >
                {t('admin.dataMigration.targetLabel')} *
              </label>
              <Select
                id="target-org"
                value={String(targetOrgId)}
                onChange={(e) => {
                  onTargetOrgIdChange(Number(e.target.value))
                }}
                className="w-full"
              >
                <option value={0}>{t('admin.dataMigration.selectPlaceholder')}</option>
                {organizations.map((org) => (
                  <option key={org.id} value={org.id}>
                    {org.name} ({org.slug})
                  </option>
                ))}
              </Select>
            </div>

            <div>
              <Button
                variant="primary"
                disabled={targetOrgId <= 0 || isAssigning}
                onClick={onAssign}
              >
                {isAssigning
                  ? t('admin.dataMigration.migrating')
                  : t('admin.dataMigration.assignSubmit')}
              </Button>
            </div>
          </Stack>
        </Card>
      )}
    </Stack>
  )
}
