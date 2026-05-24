import { useState } from 'react'
import type { Entity, EntityStatus } from '@/entities/entity'
import { useUpdateEntity } from '@/entities/entity'
import { Button, Stack, Text } from '@/shared/ui'

const STATUS_LABELS: Record<EntityStatus, string> = {
  draft: 'Draft',
  published: 'Published',
  archived: 'Archived',
}

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

interface EntityStatusPanelProps {
  entity: Entity
}

export function EntityStatusPanel({ entity }: EntityStatusPanelProps) {
  const updateMutation = useUpdateEntity()
  const [errorMessage, setErrorMessage] = useState<string | null>(null)

  const changeStatus = async (nextStatus: EntityStatus) => {
    setErrorMessage(null)
    try {
      await updateMutation.mutateAsync({
        id: Number(entity.id),
        entityTypeId: entity.entityTypeId,
        status: nextStatus,
      })
    } catch {
      setErrorMessage('Failed to update status.')
    }
  }

  const currentStatus = entity.status

  return (
    <Stack gap="sm">
      <Text as="h2" variant="heading-sm">
        Publish status
      </Text>
      <div className="flex flex-wrap items-center gap-inline-md">
        <span className={STATUS_BADGE_CLASS[currentStatus]}>{STATUS_LABELS[currentStatus]}</span>
        {entity.publishedAt !== null && (
          <Text as="span" muted>
            Published {new Date(entity.publishedAt).toLocaleDateString('ja-JP')}
          </Text>
        )}
      </div>
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
            {nextStatus === 'published' ? 'Publish' : STATUS_LABELS[nextStatus]}
          </Button>
        ))}
      </div>
      {errorMessage !== null && <Text muted>{errorMessage}</Text>}
    </Stack>
  )
}
