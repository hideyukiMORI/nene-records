import { useState } from 'react'
import type { Entity, EntityStatus } from '@/entities/entity'
import {
  useGeneratePreviewToken,
  useRevokePreviewToken,
  useScheduleEntity,
  useUnscheduleEntity,
  useUpdateEntity,
} from '@/entities/entity'
import { useTranslation } from '@/shared/i18n'
import { useToast } from '@/shared/ui'

export interface EntityStatusPanelState {
  entity: Entity
  entityTypeSlug?: string
  slugInput: string
  showScheduleForm: boolean
  scheduledAtInput: string
  previewUrl: string | null
  previewExpires: string | null
  isPending: boolean
  onSlugInputChange: (value: string) => void
  onScheduledAtChange: (value: string) => void
  onToggleScheduleForm: () => void
  onCancelScheduleForm: () => void
  onChangeStatus: (nextStatus: EntityStatus) => void
  onSaveSlug: () => void
  onSchedulePublish: () => void
  onCancelSchedule: () => void
  onGeneratePreview: () => void
  onRevokePreview: () => void
}

export function useEntityStatusPanel(
  entity: Entity,
  entityTypeSlug?: string,
): EntityStatusPanelState {
  const { t } = useTranslation()
  const { showToast } = useToast()
  const updateMutation = useUpdateEntity()
  const scheduleMutation = useScheduleEntity()
  const unscheduleMutation = useUnscheduleEntity()
  const generatePreviewMutation = useGeneratePreviewToken()
  const revokePreviewMutation = useRevokePreviewToken()

  const [slugInput, setSlugInput] = useState(entity.slug ?? '')
  const [showScheduleForm, setShowScheduleForm] = useState(false)
  const [scheduledAtInput, setScheduledAtInput] = useState('')
  const [previewUrl, setPreviewUrl] = useState<string | null>(null)
  const [previewExpires, setPreviewExpires] = useState<string | null>(null)

  const isPending =
    updateMutation.isPending ||
    scheduleMutation.isPending ||
    unscheduleMutation.isPending ||
    generatePreviewMutation.isPending ||
    revokePreviewMutation.isPending

  const onChangeStatus = async (nextStatus: EntityStatus) => {
    try {
      await updateMutation.mutateAsync({
        id: Number(entity.id),
        entityTypeId: entity.entityTypeId,
        slug: slugInput !== '' ? slugInput : null,
        status: nextStatus,
      })
    } catch {
      showToast(t('admin.entityStatus.updateError'), 'error')
    }
  }

  const onSaveSlug = async () => {
    try {
      await updateMutation.mutateAsync({
        id: Number(entity.id),
        entityTypeId: entity.entityTypeId,
        slug: slugInput !== '' ? slugInput : null,
        status: entity.status,
      })
      showToast(t('admin.entityStatus.slugSaved'), 'success')
    } catch {
      showToast(t('admin.entityStatus.slugError'), 'error')
    }
  }

  const onSchedulePublish = async () => {
    if (scheduledAtInput === '') return
    try {
      await scheduleMutation.mutateAsync({
        id: Number(entity.id),
        scheduledAt: new Date(scheduledAtInput).toISOString(),
      })
      setShowScheduleForm(false)
      setScheduledAtInput('')
    } catch {
      showToast(t('admin.entityStatus.scheduleError'), 'error')
    }
  }

  const onCancelSchedule = async () => {
    try {
      await unscheduleMutation.mutateAsync({ id: entity.id })
    } catch {
      showToast(t('admin.entityStatus.updateError'), 'error')
    }
  }

  const onGeneratePreview = async () => {
    try {
      const output = await generatePreviewMutation.mutateAsync({ id: Number(entity.id) })
      setPreviewUrl(output.previewUrl)
      setPreviewExpires(output.expiresAt)
    } catch {
      showToast(t('admin.entityStatus.previewTokenError'), 'error')
    }
  }

  const onRevokePreview = async () => {
    try {
      await revokePreviewMutation.mutateAsync({ id: Number(entity.id) })
      setPreviewUrl(null)
      setPreviewExpires(null)
    } catch {
      showToast(t('admin.entityStatus.updateError'), 'error')
    }
  }

  return {
    entity,
    entityTypeSlug,
    slugInput,
    showScheduleForm,
    scheduledAtInput,
    previewUrl,
    previewExpires,
    isPending,
    onSlugInputChange: setSlugInput,
    onScheduledAtChange: setScheduledAtInput,
    onToggleScheduleForm: () => {
      setShowScheduleForm((prev) => !prev)
    },
    onCancelScheduleForm: () => {
      setShowScheduleForm(false)
      setScheduledAtInput('')
    },
    onChangeStatus: (nextStatus) => {
      void onChangeStatus(nextStatus)
    },
    onSaveSlug: () => {
      void onSaveSlug()
    },
    onSchedulePublish: () => {
      void onSchedulePublish()
    },
    onCancelSchedule: () => {
      void onCancelSchedule()
    },
    onGeneratePreview: () => {
      void onGeneratePreview()
    },
    onRevokePreview: () => {
      void onRevokePreview()
    },
  }
}
