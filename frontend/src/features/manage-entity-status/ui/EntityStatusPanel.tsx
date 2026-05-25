import { useState } from 'react'
import type { Entity, EntityStatus } from '@/entities/entity'
import { useUpdateEntity } from '@/entities/entity'
import { useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import { Button, Input, Stack, Text } from '@/shared/ui'

const STATUS_BADGE_CLASS: Record<EntityStatus, string> = {
  draft:
    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800',
  published:
    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-800',
  archived:
    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-600',
}

const NEXT_STATUSES: Record<EntityStatus, EntityStatus[]> = {
  draft: ['published', 'archived'],
  published: ['draft', 'archived'],
  archived: ['draft', 'published'],
}

const STATUS_LABEL_KEYS: Record<EntityStatus, MessageKey> = {
  draft: 'admin.entityStatus.status.draft',
  published: 'admin.entityStatus.status.published',
  archived: 'admin.entityStatus.status.archived',
}

interface EntityStatusPanelProps {
  entity: Entity
  entityTypeSlug?: string
}

export function EntityStatusPanel({ entity, entityTypeSlug }: EntityStatusPanelProps) {
  const { t } = useTranslation()
  const updateMutation = useUpdateEntity()
  const [errorMessage, setErrorMessage] = useState<string | null>(null)
  const [slugInput, setSlugInput] = useState(entity.slug ?? '')
  const [slugSaved, setSlugSaved] = useState(false)

  const changeStatus = async (nextStatus: EntityStatus) => {
    setErrorMessage(null)
    setSlugSaved(false)
    try {
      await updateMutation.mutateAsync({
        id: Number(entity.id),
        entityTypeId: entity.entityTypeId,
        slug: slugInput !== '' ? slugInput : null,
        status: nextStatus,
      })
    } catch {
      setErrorMessage(t('admin.entityStatus.updateError'))
    }
  }

  const saveSlug = async () => {
    setErrorMessage(null)
    setSlugSaved(false)
    try {
      await updateMutation.mutateAsync({
        id: Number(entity.id),
        entityTypeId: entity.entityTypeId,
        slug: slugInput !== '' ? slugInput : null,
        status: entity.status,
      })
      setSlugSaved(true)
    } catch {
      setErrorMessage(t('admin.entityStatus.slugError'))
    }
  }

  const currentStatus = entity.status
  const publicUrl =
    entityTypeSlug !== undefined && (entity.slug ?? slugInput) !== ''
      ? `/view/${entityTypeSlug}/${entity.slug ?? slugInput}`
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
              setSlugSaved(false)
            }}
            placeholder={t('admin.entityStatus.slugPlaceholder')}
          />
          <Button variant="secondary" size="sm" onClick={() => void saveSlug()}>
            {t('admin.entityStatus.saveSlug')}
          </Button>
        </div>
        {slugSaved && <Text muted>{t('admin.entityStatus.slugSaved')}</Text>}
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
            disabled={updateMutation.isPending}
            onClick={() => {
              void changeStatus(nextStatus)
            }}
          >
            {nextStatus === 'published'
              ? t('admin.entityStatus.publish')
              : t(STATUS_LABEL_KEYS[nextStatus])}
          </Button>
        ))}
      </div>
      {errorMessage !== null && <Text muted>{errorMessage}</Text>}
    </Stack>
  )
}
