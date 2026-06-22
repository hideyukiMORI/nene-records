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
  const hasUnassigned = (migrationStatus?.total ?? 0) > 0

  return (
    <Stack gap="lg">
      <PageHeader
        title="Data Migration"
        description={
          <>
            Assign legacy records (organization_id&nbsp;= 0) to a specific organization. Use this
            when migrating from single-tenant to multi-tenant mode.
          </>
        }
      />

      {/* Status panel */}
      <Card padding="none" className="p-6">
        <Text as="h2" variant="heading-sm">
          Unassigned Records
        </Text>

        {isStatusLoading && (
          <Text muted className="mt-3">
            Loading…
          </Text>
        )}
        {isStatusError && (
          <Text muted className="mt-3 text-red-500">
            Failed to load migration status.
          </Text>
        )}

        {migrationStatus !== undefined && (
          <>
            <div className="mt-3 mb-4">
              {hasUnassigned ? (
                <div className="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 dark:bg-amber-950/20 dark:border-amber-900/40">
                  <Text className="font-semibold text-amber-800 dark:text-amber-300">
                    {migrationStatus.total.toLocaleString()} unassigned record
                    {migrationStatus.total !== 1 ? 's' : ''} found
                  </Text>
                </div>
              ) : (
                <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 dark:bg-green-950/20 dark:border-green-900/40">
                  <Text className="font-semibold text-green-800 dark:text-green-300">
                    All records are assigned to an organization. ✓
                  </Text>
                </div>
              )}
            </div>

            {hasUnassigned && (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b border-border">
                      <th className="pb-2 text-left font-medium text-text-secondary">Table</th>
                      <th className="pb-2 text-right font-medium text-text-secondary">
                        Unassigned rows
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
            Assign to Organization
          </Text>
          <Text muted className="mt-1">
            All unassigned records will be moved to the selected organization. This cannot be
            undone.
          </Text>

          <Stack gap="md" className="mt-4">
            <div>
              <label
                htmlFor="target-org"
                className="mb-1 block text-sm font-medium text-text-primary"
              >
                Target organization *
              </label>
              <Select
                id="target-org"
                value={String(targetOrgId)}
                onChange={(e) => {
                  onTargetOrgIdChange(Number(e.target.value))
                }}
                className="w-full"
              >
                <option value={0}>— Select organization —</option>
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
                {isAssigning ? 'Migrating…' : 'Assign Records'}
              </Button>
            </div>
          </Stack>
        </Card>
      )}
    </Stack>
  )
}
