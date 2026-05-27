import { useState } from 'react'
import { useDataMigrationStatus, useAssignOrg } from '@/entities/data-migration'
import { useOrganizationList } from '@/entities/organization'
import { Button, Stack, Text } from '@/shared/ui'
import { useToast } from '@/shared/ui'

export function DataMigrationPage() {
  const { showToast } = useToast()
  const { data: status, isLoading: statusLoading, isError: statusError } = useDataMigrationStatus()
  const { data: orgs } = useOrganizationList()
  const assignOrg = useAssignOrg()

  const [targetOrgId, setTargetOrgId] = useState<number>(0)

  const hasUnassigned = (status?.total ?? 0) > 0

  function handleAssign() {
    if (targetOrgId <= 0) {
      showToast('Please select a target organization.', 'error')
      return
    }

    assignOrg.mutate(
      { targetOrgId },
      {
        onSuccess: (result) => {
          showToast(
            `Migrated ${String(result.total)} records to "${result.organizationName}".`,
            'success',
          )
        },
        onError: (err) => {
          showToast(err.title, 'error')
        },
      },
    )
  }

  return (
    <Stack gap="lg">
      <div>
        <Text as="h1" variant="heading-md">
          Data Migration
        </Text>
        <Text muted>
          Assign legacy records (organization_id&nbsp;= 0) to a specific organization. Use this when
          migrating from single-tenant to multi-tenant mode.
        </Text>
      </div>

      {/* Status panel */}
      <div className="rounded-lg border border-border bg-surface-raised p-6">
        <Text as="h2" variant="heading-sm">
          Unassigned Records
        </Text>

        {statusLoading && (
          <Text muted className="mt-3">
            Loading…
          </Text>
        )}
        {statusError && (
          <Text muted className="mt-3 text-red-500">
            Failed to load migration status.
          </Text>
        )}

        {status !== undefined && (
          <>
            <div className="mt-3 mb-4">
              {hasUnassigned ? (
                <div className="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 dark:bg-amber-950/20 dark:border-amber-900/40">
                  <Text className="font-semibold text-amber-800 dark:text-amber-300">
                    {status.total.toLocaleString()} unassigned record
                    {status.total !== 1 ? 's' : ''} found
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
                    {Object.entries(status.tables)
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
      </div>

      {/* Assign panel */}
      {hasUnassigned && (
        <div className="rounded-lg border border-border bg-surface-raised p-6">
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
              <select
                id="target-org"
                value={targetOrgId}
                onChange={(e) => {
                  setTargetOrgId(Number(e.target.value))
                }}
                className="w-full rounded-md border border-border bg-surface px-3 py-2 text-sm text-text-primary focus:border-accent focus:outline-none"
              >
                <option value={0}>— Select organization —</option>
                {orgs?.items.map((org) => (
                  <option key={org.id} value={org.id}>
                    {org.name} ({org.slug})
                  </option>
                ))}
              </select>
            </div>

            <div>
              <Button
                variant="primary"
                disabled={targetOrgId <= 0 || assignOrg.isPending}
                onClick={handleAssign}
              >
                {assignOrg.isPending ? 'Migrating…' : 'Assign Records'}
              </Button>
            </div>
          </Stack>
        </div>
      )}
    </Stack>
  )
}
