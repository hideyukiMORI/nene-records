import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import {
  useOrganization,
  useUpdateOrganization,
  useDeleteOrganization,
} from '@/entities/organization'
import type { Organization, UpdateOrganizationInput } from '@/entities/organization'
import { fetchOrgExport, useImportOrg } from '@/entities/org-export'
import { useTranslation } from '@/shared/i18n'
import { useToast } from '@/shared/ui'

export interface ManageOrganizationDetailPageState {
  org: Organization | undefined
  isLoading: boolean
  isError: boolean
  isUpdating: boolean
  isDeleting: boolean
  isExporting: boolean
  isImporting: boolean
  showDeleteConfirm: boolean
  currentName: string
  currentSlug: string
  currentPlan: string
  currentIsActive: boolean
  currentCustomDomain: string
  onNameChange: (value: string) => void
  onSlugChange: (value: string) => void
  onPlanChange: (value: string) => void
  onIsActiveChange: (value: boolean) => void
  onCustomDomainChange: (value: string) => void
  onUpdate: (e: React.SyntheticEvent) => void
  onExport: () => Promise<void>
  onImportFile: (file: File) => void
  onDeleteRequest: () => void
  onDeleteConfirm: () => void
  onDeleteCancel: () => void
}

export function useManageOrganizationDetailPage(orgId: number): ManageOrganizationDetailPageState {
  const navigate = useNavigate()
  const { t } = useTranslation()
  const { showToast } = useToast()

  const { data: org, isLoading, isError } = useOrganization(orgId)
  const updateOrg = useUpdateOrganization()
  const deleteOrg = useDeleteOrganization()
  const importOrg = useImportOrg(orgId)

  const [name, setName] = useState<string | undefined>(undefined)
  const [slug, setSlug] = useState<string | undefined>(undefined)
  const [plan, setPlan] = useState<string | undefined>(undefined)
  const [isActive, setIsActive] = useState<boolean | undefined>(undefined)
  const [customDomain, setCustomDomain] = useState<string | undefined>(undefined)
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false)
  const [isExporting, setIsExporting] = useState(false)

  const currentName = name ?? org?.name ?? ''
  const currentSlug = slug ?? org?.slug ?? ''
  const currentPlan = plan ?? org?.plan ?? 'free'
  const currentIsActive = isActive ?? org?.isActive ?? true
  const currentCustomDomain = customDomain ?? org?.customDomain ?? ''

  const onUpdate = (e: React.SyntheticEvent) => {
    e.preventDefault()

    const input: UpdateOrganizationInput = {
      name: currentName.trim(),
      slug: currentSlug.trim(),
      plan: currentPlan,
      isActive: currentIsActive,
      customDomain: currentCustomDomain.trim() !== '' ? currentCustomDomain.trim() : null,
    }

    updateOrg.mutate(
      { id: orgId, input },
      {
        onSuccess: () => {
          showToast(t('admin.organizations.toast.updated'), 'success')
        },
        onError: (err) => {
          showToast(err.title, 'error')
        },
      },
    )
  }

  const onExport = async () => {
    setIsExporting(true)
    try {
      const payload = await fetchOrgExport(orgId)
      const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' })
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `org-${String(orgId)}-export.json`
      a.click()
      URL.revokeObjectURL(url)
    } catch {
      showToast(t('admin.organizations.toast.exportFailed'), 'error')
    } finally {
      setIsExporting(false)
    }
  }

  const onImportFile = (file: File) => {
    const reader = new FileReader()
    reader.onload = (event) => {
      try {
        const raw = event.target?.result
        if (typeof raw !== 'string') return
        const payload = JSON.parse(raw) as Record<string, unknown>
        importOrg.mutate(payload, {
          onSuccess: (result) => {
            showToast(
              t('admin.organizations.toast.imported', {
                count: result.total,
                name: result.organizationName,
              }),
              'success',
            )
          },
          onError: (err) => {
            showToast(err.title, 'error')
          },
        })
      } catch {
        showToast(t('admin.organizations.toast.invalidJson'), 'error')
      }
    }
    reader.readAsText(file)
  }

  const onDeleteConfirm = () => {
    const orgName = org?.name ?? ''
    deleteOrg.mutate(orgId, {
      onSuccess: () => {
        showToast(t('admin.organizations.toast.deleted', { name: orgName }), 'success')
        void navigate('/superadmin/organizations')
      },
      onError: (err) => {
        showToast(err.title, 'error')
        setShowDeleteConfirm(false)
      },
    })
  }

  return {
    org,
    isLoading,
    isError,
    isUpdating: updateOrg.isPending,
    isDeleting: deleteOrg.isPending,
    isExporting,
    isImporting: importOrg.isPending,
    showDeleteConfirm,
    currentName,
    currentSlug,
    currentPlan,
    currentIsActive,
    currentCustomDomain,
    onNameChange: setName,
    onSlugChange: setSlug,
    onPlanChange: setPlan,
    onIsActiveChange: setIsActive,
    onCustomDomainChange: setCustomDomain,
    onUpdate,
    onExport: () => onExport(),
    onImportFile,
    onDeleteRequest: () => {
      setShowDeleteConfirm(true)
    },
    onDeleteConfirm,
    onDeleteCancel: () => {
      setShowDeleteConfirm(false)
    },
  }
}
