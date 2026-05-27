import { useState } from 'react'
import type {
  CreateNotificationChannelInput,
  NotificationChannel,
  NotificationChannelList,
  NotificationChannelType,
  UpdateNotificationChannelInput,
} from '@/entities/notification-channel'
import {
  useCreateNotificationChannel,
  useDeleteNotificationChannel,
  useNotificationChannelList,
  useTestNotificationChannel,
  useUpdateNotificationChannel,
} from '@/entities/notification-channel'
import { useTranslation } from '@/shared/i18n'

export interface ManageNotificationChannelsPageState {
  channels: NotificationChannelList | undefined
  isLoading: boolean
  isError: boolean
  showCreateForm: boolean
  editingId: number | null
  deleteTarget: NotificationChannel | null
  testingId: number | null
  isCreating: boolean
  isUpdating: boolean
  isDeleting: boolean
  isTesting: boolean
  createError: string | null
  updateError: string | null
  testSuccess: number | null
  testError: number | null
  onCreate: (input: CreateNotificationChannelInput) => Promise<void>
  onUpdate: (id: number, input: UpdateNotificationChannelInput) => Promise<void>
  onToggleEnabled: (channel: NotificationChannel) => Promise<void>
  onTest: (id: number) => Promise<void>
  onDeleteConfirm: () => Promise<void>
  onShowCreateForm: (channelType?: NotificationChannelType) => void
  onHideCreateForm: () => void
  onStartEdit: (id: number) => void
  onCancelEdit: () => void
  onDeleteRequest: (channel: NotificationChannel) => void
  onDeleteCancel: () => void
}

export function useManageNotificationChannelsPage(): ManageNotificationChannelsPageState {
  const { t } = useTranslation()
  const { data, isLoading, isError } = useNotificationChannelList()
  const createMutation = useCreateNotificationChannel()
  const updateMutation = useUpdateNotificationChannel()
  const deleteMutation = useDeleteNotificationChannel()
  const testMutation = useTestNotificationChannel()

  const [showCreateForm, setShowCreateForm] = useState(false)
  const [editingId, setEditingId] = useState<number | null>(null)
  const [deleteTarget, setDeleteTarget] = useState<NotificationChannel | null>(null)
  const [testingId, setTestingId] = useState<number | null>(null)
  const [createError, setCreateError] = useState<string | null>(null)
  const [updateError, setUpdateError] = useState<string | null>(null)
  const [testSuccess, setTestSuccess] = useState<number | null>(null)
  const [testError, setTestError] = useState<number | null>(null)

  async function onCreate(input: CreateNotificationChannelInput) {
    setCreateError(null)
    try {
      await createMutation.mutateAsync(input)
      setShowCreateForm(false)
    } catch {
      setCreateError(t('admin.notifications.createError'))
    }
  }

  async function onUpdate(id: number, input: UpdateNotificationChannelInput) {
    setUpdateError(null)
    try {
      await updateMutation.mutateAsync({ id, input })
      setEditingId(null)
    } catch {
      setUpdateError(t('admin.notifications.updateError'))
    }
  }

  async function onToggleEnabled(channel: NotificationChannel) {
    await updateMutation.mutateAsync({
      id: channel.id,
      input: {
        label: channel.label,
        isEnabled: !channel.isEnabled,
        config: channel.config,
      },
    })
  }

  async function onTest(id: number) {
    setTestingId(id)
    setTestSuccess(null)
    setTestError(null)
    try {
      await testMutation.mutateAsync(id)
      setTestSuccess(id)
      setTimeout(() => {
        setTestSuccess(null)
      }, 3000)
    } catch {
      setTestError(id)
      setTimeout(() => {
        setTestError(null)
      }, 3000)
    } finally {
      setTestingId(null)
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
    channels: data,
    isLoading,
    isError,
    showCreateForm,
    editingId,
    deleteTarget,
    testingId,
    isCreating: createMutation.isPending,
    isUpdating: updateMutation.isPending,
    isDeleting: deleteMutation.isPending,
    isTesting: testMutation.isPending,
    createError,
    updateError,
    testSuccess,
    testError,
    onCreate,
    onUpdate,
    onToggleEnabled,
    onTest,
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
      setUpdateError(null)
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
