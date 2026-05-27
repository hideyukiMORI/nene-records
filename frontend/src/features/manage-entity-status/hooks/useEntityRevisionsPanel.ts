import { useState } from 'react'
import type { EntityRevision } from '@/entities/entity'
import { toEntityId, useEntityRevisions } from '@/entities/entity'

export interface EntityRevisionsPanelState {
  revisions: EntityRevision[]
  isLoading: boolean
  isError: boolean
  isExpanded: boolean
  onToggle: () => void
}

export function useEntityRevisionsPanel(entityId: number): EntityRevisionsPanelState {
  const [isExpanded, setIsExpanded] = useState(false)
  const revisionsQuery = useEntityRevisions(toEntityId(entityId))

  function onToggle() {
    setIsExpanded((prev) => !prev)
  }

  return {
    revisions: revisionsQuery.data?.items ?? [],
    isLoading: revisionsQuery.isLoading,
    isError: revisionsQuery.isError,
    isExpanded,
    onToggle,
  }
}
