import { useState } from 'react'
import type { Entity } from '@/entities/entity'
import { useUpdateEntity } from '@/entities/entity'
import { useTranslation } from '@/shared/i18n'
import { useToast } from '@/shared/ui'

export interface EntitySeoPanelState {
  metaTitle: string
  metaDescription: string
  isPending: boolean
  onMetaTitleChange: (value: string) => void
  onMetaDescriptionChange: (value: string) => void
  onSave: () => void
}

/**
 * Orchestration hook for EntitySeoPanel.
 * Accepts a non-null entity — call site should guard with `entity !== null`.
 */
export function useEntitySeoPanel(entity: Entity): EntitySeoPanelState {
  const { t } = useTranslation()
  const { showToast } = useToast()
  const updateMutation = useUpdateEntity()

  const [metaTitle, setMetaTitle] = useState(entity.metaTitle ?? '')
  const [metaDescription, setMetaDescription] = useState(entity.metaDescription ?? '')

  function onSave() {
    void updateMutation
      .mutateAsync({
        id: Number(entity.id),
        entityTypeId: entity.entityTypeId,
        slug: entity.slug,
        status: entity.status,
        metaTitle: metaTitle !== '' ? metaTitle : null,
        metaDescription: metaDescription !== '' ? metaDescription : null,
        // Preserve layout / visibility flags: the update endpoint is full-replace.
        layout: entity.layout,
        showComments: entity.showComments,
        showRelated: entity.showRelated,
      })
      .then(() => {
        showToast(t('admin.entitySeo.saveSuccess'), 'success')
      })
      .catch(() => {
        showToast(t('common.error.serverError'), 'error')
      })
  }

  return {
    metaTitle,
    metaDescription,
    isPending: updateMutation.isPending,
    onMetaTitleChange: setMetaTitle,
    onMetaDescriptionChange: setMetaDescription,
    onSave,
  }
}
