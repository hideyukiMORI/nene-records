import { useState } from 'react'
import {
  useOrganizationList,
  useCreateOrganization,
  useDeleteOrganization,
} from '@/entities/organization'
import type { CreateOrganizationInput, Organization } from '@/entities/organization'
import { useTranslation } from '@/shared/i18n'
import { useToast } from '@/shared/ui'

export interface ManageOrganizationsPageState {
  organizations: Organization[]
  total: number
  isLoading: boolean
  isError: boolean
  showCreateForm: boolean
  deleteTarget: Organization | null
  isCreating: boolean
  isDeleting: boolean
  onShowCreateForm: () => void
  onHideCreateForm: () => void
  onCreate: (input: CreateOrganizationInput) => void
  onSetDeleteTarget: (org: Organization | null) => void
  onConfirmDelete: () => void
}

export function useManageOrganizationsPage(): ManageOrganizationsPageState {
  const { t } = useTranslation()
  const { showToast } = useToast()
  const { data, isLoading, isError } = useOrganizationList()
  const createOrg = useCreateOrganization()
  const deleteOrg = useDeleteOrganization()

  const [showCreateForm, setShowCreateForm] = useState(false)
  const [deleteTarget, setDeleteTarget] = useState<Organization | null>(null)

  const onCreate = (input: CreateOrganizationInput) => {
    createOrg.mutate(input, {
      onSuccess: () => {
        showToast(t('admin.organizations.toast.created', { name: input.name }), 'success')
        setShowCreateForm(false)
      },
      onError: (err) => {
        showToast(err.title, 'error')
      },
    })
  }

  const onConfirmDelete = () => {
    if (deleteTarget === null) return
    const targetName = deleteTarget.name
    const targetId = deleteTarget.id
    deleteOrg.mutate(targetId, {
      onSuccess: () => {
        showToast(t('admin.organizations.toast.deleted', { name: targetName }), 'success')
        setDeleteTarget(null)
      },
      onError: (err) => {
        showToast(err.title, 'error')
        setDeleteTarget(null)
      },
    })
  }

  return {
    organizations: data?.items ?? [],
    total: data?.total ?? 0,
    isLoading,
    isError,
    showCreateForm,
    deleteTarget,
    isCreating: createOrg.isPending,
    isDeleting: deleteOrg.isPending,
    onShowCreateForm: () => {
      setShowCreateForm(true)
    },
    onHideCreateForm: () => {
      setShowCreateForm(false)
    },
    onCreate,
    onSetDeleteTarget: setDeleteTarget,
    onConfirmDelete,
  }
}
