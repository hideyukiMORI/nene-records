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
import type { MessageKey } from '@/shared/i18n'
import { Button, Input, Stack, Text, useToast } from '@/shared/ui'

const STATUS_BADGE_CLASS: Record<EntityStatus, string> = {
  draft:
    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800',
  published:
    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-800',
  archived:
    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-600',
  scheduled:
    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-blue-100 text-blue-800',
}

const NEXT_STATUSES: Record<EntityStatus, EntityStatus[]> = {
  draft: ['published', 'archived'],
  published: ['draft', 'archived'],
  archived: ['draft', 'published'],
  scheduled: ['published', 'draft'],
}

const STATUS_LABEL_KEYS: Record<EntityStatus, MessageKey> = {
  draft: 'admin.entityStatus.status.draft',
  published: 'admin.entityStatus.status.published',
  archived: 'admin.entityStatus.status.archived',
  scheduled: 'admin.entityStatus.status.scheduled',
}

interface EntityStatusPanelProps {
  entity: Entity
  entityTypeSlug?: string
}

export function EntityStatusPanel({ entity, entityTypeSlug }: EntityStatusPanelProps) {
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

  const changeStatus = async (nextStatus: EntityStatus) => {
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

  const saveSlug = async () => {
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

  const schedulePublish = async () => {
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

  const cancelSchedule = async () => {
    try {
      await unscheduleMutation.mutateAsync({ id: entity.id })
    } catch {
      showToast(t('admin.entityStatus.updateError'), 'error')
    }
  }

  const generatePreview = async () => {
    try {
      const output = await generatePreviewMutation.mutateAsync({ id: Number(entity.id) })
      setPreviewUrl(output.previewUrl)
      setPreviewExpires(output.expiresAt)
    } catch {
      showToast(t('admin.entityStatus.previewTokenError'), 'error')
    }
  }

  const revokePreview = async () => {
    try {
      await revokePreviewMutation.mutateAsync({ id: Number(entity.id) })
      setPreviewUrl(null)
      setPreviewExpires(null)
    } catch {
      showToast(t('admin.entityStatus.updateError'), 'error')
    }
  }

  const currentStatus = entity.status
  const publicUrl =
    entityTypeSlug !== undefined && (entity.slug ?? slugInput) !== ''
      ? `/${entityTypeSlug}/${entity.slug ?? slugInput}`
      : null

  return (
    <Stack gap="sm">
      <Text as="h2" variant="heading-sm">
        {t('admin.entityStatus.panelTitle')}
      </Text>
      <div className="flex flex-wrap items-center gap-inline-md">
        <span className={STATUS_BADGE_CLASS[currentStatus]}>
          {t(STATUS_LABEL_KEYS[currentStatus])}
        </span>
        {entity.publishedAt !== null && (
          <Text as="span" muted>
            {t('admin.entityStatus.publishedAt', {
              date: new Date(entity.publishedAt).toLocaleDateString(),
            })}
          </Text>
        )}
        {entity.scheduledAt !== null && (
          <Text as="span" muted>
            {t('admin.entityStatus.scheduledAt', {
              date: new Date(entity.scheduledAt).toLocaleString(),
            })}
          </Text>
        )}
      </div>

      <Stack gap="xs">
        <label htmlFor="entity-slug" className="text-sm font-medium text-text-primary">
          {t('admin.entityStatus.slugLabel')}
        </label>
        <div className="flex items-center gap-inline-sm">
          <Input
            id="entity-slug"
            value={slugInput}
            onChange={(e) => {
              setSlugInput(e.target.value)
            }}
            placeholder={t('admin.entityStatus.slugPlaceholder')}
          />
          <Button variant="secondary" size="sm" onClick={() => void saveSlug()}>
            {t('admin.entityStatus.saveSlug')}
          </Button>
        </div>
        {publicUrl !== null && (
          <a
            href={publicUrl}
            target="_blank"
            rel="noopener noreferrer"
            className="text-sm text-blue-600 underline hover:text-blue-800"
          >
            {publicUrl}
          </a>
        )}
      </Stack>

      <div className="flex flex-wrap gap-inline-sm">
        {NEXT_STATUSES[currentStatus].map((nextStatus) => (
          <Button
            key={nextStatus}
            variant="secondary"
            size="sm"
            disabled={isPending}
            onClick={() => {
              void changeStatus(nextStatus)
            }}
          >
            {nextStatus === 'published'
              ? t('admin.entityStatus.publish')
              : t(STATUS_LABEL_KEYS[nextStatus])}
          </Button>
        ))}
        {currentStatus !== 'scheduled' && (
          <Button
            variant="secondary"
            size="sm"
            disabled={isPending}
            onClick={() => {
              setShowScheduleForm(!showScheduleForm)
            }}
          >
            {t('admin.entityStatus.schedule')}
          </Button>
        )}
        {currentStatus === 'scheduled' && (
          <Button
            variant="secondary"
            size="sm"
            disabled={isPending}
            onClick={() => void cancelSchedule()}
          >
            {t('admin.entityStatus.cancelSchedule')}
          </Button>
        )}
        <Button
          variant="secondary"
          size="sm"
          disabled={isPending}
          onClick={() => void generatePreview()}
        >
          {t('admin.entityStatus.generatePreviewToken')}
        </Button>
      </div>

      {showScheduleForm && (
        <div className="flex items-center gap-inline-sm rounded-md border border-border bg-surface p-3">
          <input
            type="datetime-local"
            value={scheduledAtInput}
            onChange={(e) => {
              setScheduledAtInput(e.target.value)
            }}
            className="rounded-md border border-border bg-surface px-3 py-2 text-sm text-text-primary focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
          />
          <Button
            size="sm"
            disabled={isPending || scheduledAtInput === ''}
            onClick={() => void schedulePublish()}
          >
            {t('admin.entityStatus.confirmSchedule')}
          </Button>
          <Button
            variant="secondary"
            size="sm"
            onClick={() => {
              setShowScheduleForm(false)
              setScheduledAtInput('')
            }}
          >
            {t('common.actions.cancel')}
          </Button>
        </div>
      )}

      {previewUrl !== null && (
        <div className="rounded-md border border-border bg-surface p-3">
          <Stack gap="xs">
            <Text as="span" muted>
              {t('admin.entityStatus.previewTokenExpires', {
                date: previewExpires !== null ? new Date(previewExpires).toLocaleString() : '',
              })}
            </Text>
            <div className="flex items-center gap-inline-sm">
              <a
                href={previewUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="flex-1 truncate text-sm text-blue-600 underline hover:text-blue-800"
              >
                {previewUrl}
              </a>
              <Button
                variant="secondary"
                size="sm"
                disabled={isPending}
                onClick={() => void revokePreview()}
              >
                {t('admin.entityStatus.revokePreviewToken')}
              </Button>
            </div>
          </Stack>
        </div>
      )}
    </Stack>
  )
}
