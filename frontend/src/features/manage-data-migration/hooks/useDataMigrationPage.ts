import { useState } from 'react'
import { useDataMigrationStatus, useAssignOrg } from '@/entities/data-migration'
import { useOrganizationList } from '@/entities/organization'
import type { Organization } from '@/entities/organization'
import { useToast } from '@/shared/ui'

export interface DataMigrationPageState {
  migrationStatus: { total: number; tables: Record<string, number> } | undefined
  organizations: Organization[]
  isStatusLoading: boolean
  isStatusError: boolean
  isAssigning: boolean
  targetOrgId: number
  onTargetOrgIdChange: (id: number) => void
  onAssign: () => void
}

export function useDataMigrationPage(): DataMigrationPageState {
  const { showToast } = useToast()
  const { data: status, isLoading: statusLoading, isError: statusError } = useDataMigrationStatus()
  const { data: orgs } = useOrganizationList()
  const assignOrg = useAssignOrg()

  const [targetOrgId, setTargetOrgId] = useState<number>(0)

  const onAssign = () => {
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

  return {
    migrationStatus: status,
    organizations: orgs?.items ?? [],
    isStatusLoading: statusLoading,
    isStatusError: statusError,
    isAssigning: assignOrg.isPending,
    targetOrgId,
    onTargetOrgIdChange: setTargetOrgId,
    onAssign,
  }
}
