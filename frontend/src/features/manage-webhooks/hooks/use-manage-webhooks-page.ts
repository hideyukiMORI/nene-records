import { useState } from 'react'
import type { CreateWebhookInput, Webhook, WebhookList } from '@/entities/webhook'
import {
  useCreateWebhook,
  useDeleteWebhook,
  useUpdateWebhook,
  useWebhookList,
} from '@/entities/webhook'
import { useTranslation } from '@/shared/i18n'

export interface ManageWebhooksPageState {
  webhooks: WebhookList | undefined
  isLoading: boolean
  isError: boolean
  showCreateForm: boolean
  editingId: number | null
  deleteTarget: Webhook | null
  isCreating: boolean
  isUpdating: boolean
  isDeleting: boolean
  createError: string | null
  updateError: string | null
  onCreate: (input: CreateWebhookInput) => Promise<void>
  onUpdate: (id: number, input: CreateWebhookInput) => Promise<void>
  onDeleteConfirm: () => Promise<void>
  onShowCreateForm: () => void
  onHideCreateForm: () => void
  onStartEdit: (id: number) => void
  onCancelEdit: () => void
  onDeleteRequest: (webhook: Webhook) => void
  onDeleteCancel: () => void
}

export function useManageWebhooksPage(): ManageWebhooksPageState {
  const { t } = useTranslation()
  const { data, isLoading, isError } = useWebhookList()
  const createMutation = useCreateWebhook()
  const updateMutation = useUpdateWebhook()
  const deleteMutation = useDeleteWebhook()

  const [showCreateForm, setShowCreateForm] = useState(false)
  const [editingId, setEditingId] = useState<number | null>(null)
  const [deleteTarget, setDeleteTarget] = useState<Webhook | null>(null)
  const [createError, setCreateError] = useState<string | null>(null)
  const [updateError, setUpdateError] = useState<string | null>(null)

  async function onCreate(input: CreateWebhookInput) {
    setCreateError(null)
    try {
      await createMutation.mutateAsync(input)
      setShowCreateForm(false)
    } catch {
      setCreateError(t('admin.webhooks.createError'))
    }
  }

  async function onUpdate(id: number, input: CreateWebhookInput) {
    setUpdateError(null)
    try {
      await updateMutation.mutateAsync({ id, input })
      setEditingId(null)
    } catch {
      setUpdateError(t('admin.webhooks.updateError'))
    }
  }

  async function onDeleteConfirm() {
    if (deleteTarget === null) return
    try {
      await deleteMutation.mutateAsync(deleteTarget.id)
    } finally {
      setDeleteTarget(null)
    }
  }

  return {
    webhooks: data,
    isLoading,
    isError,
    showCreateForm,
    editingId,
    deleteTarget,
    isCreating: createMutation.isPending,
    isUpdating: updateMutation.isPending,
    isDeleting: deleteMutation.isPending,
    createError,
    updateError,
    onCreate,
    onUpdate,
    onDeleteConfirm,
    onShowCreateForm: () => {
      setShowCreateForm(true)
    },
    onHideCreateForm: () => {
      setShowCreateForm(false)
      setCreateError(null)
    },
    onStartEdit: (id: number) => {
      setEditingId(id)
    },
    onCancelEdit: () => {
      setEditingId(null)
      setUpdateError(null)
    },
    onDeleteRequest: setDeleteTarget,
    onDeleteCancel: () => {
      setDeleteTarget(null)
    },
  }
}
