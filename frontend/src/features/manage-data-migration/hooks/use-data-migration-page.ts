import { useState } from 'react'
import { useDataMigrationStatus, useAssignOrg } from '@/entities/data-migration'
import { useOrganizationList } from '@/entities/organization'
import type { Organization } from '@/entities/organization'
import { useTranslation } from '@/shared/i18n'
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
  const { t } = useTranslation()
  const { showToast } = useToast()
  const { data: status, isLoading: statusLoading, isError: statusError } = useDataMigrationStatus()
  const { data: orgs } = useOrganizationList()
  const assignOrg = useAssignOrg()

  const [targetOrgId, setTargetOrgId] = useState<number>(0)

  const onAssign = () => {
    if (targetOrgId <= 0) {
      showToast(t('admin.dataMigration.noTarget'), 'error')
      return
    }

    assignOrg.mutate(
      { targetOrgId },
      {
        onSuccess: (result) => {
          showToast(
            t('admin.dataMigration.toast.migrated', {
              count: result.total,
              name: result.organizationName,
            }),
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
