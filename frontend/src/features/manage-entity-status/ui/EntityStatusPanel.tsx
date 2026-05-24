import { useState } from 'react'
import type { Entity, EntityStatus } from '@/entities/entity'
import { useUpdateEntity } from '@/entities/entity'
import { Button, Input, Stack, Text } from '@/shared/ui'

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
  entityTypeSlug?: string
}

export function EntityStatusPanel({ entity, entityTypeSlug }: EntityStatusPanelProps) {
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
      setErrorMessage('Failed to update status.')
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
      setErrorMessage('Failed to save slug. It may already be used by another record.')
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

      <Stack gap="xs">
        <label htmlFor="entity-slug" className="text-sm font-medium text-text-primary">
          Slug
        </label>
        <div className="flex items-center gap-inline-sm">
          <Input
            id="entity-slug"
            value={slugInput}
            onChange={(e) => {
              setSlugInput(e.target.value)
              setSlugSaved(false)
            }}
            placeholder="e.g. hello-world"
          />
          <Button variant="secondary" size="sm" onClick={() => void saveSlug()}>
            Save slug
          </Button>
        </div>
        {slugSaved && <Text muted>Saved.</Text>}
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
            {nextStatus === 'published' ? 'Publish' : STATUS_LABELS[nextStatus]}
          </Button>
        ))}
      </div>
      {errorMessage !== null && <Text muted>{errorMessage}</Text>}
    </Stack>
  )
}
