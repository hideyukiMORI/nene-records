import { useCallback, useMemo, useState } from 'react'
import {
  useAttachEntityTag,
  useDetachEntityTag,
  useEntityTagList,
  type EntityTag,
} from '@/entities/entity-tag'
import { useTagList, toTagId, type Tag } from '@/entities/tag'

export function useManageEntityTagsPage(entityId: number) {
  const entityTagQuery = useEntityTagList(entityId)
  const tagListQuery = useTagList({ limit: 100, offset: 0 })
  const attachMutation = useAttachEntityTag()
  const detachMutation = useDetachEntityTag()
  const [selectedTagId, setSelectedTagId] = useState('')

  const attachedTags = useMemo(() => entityTagQuery.data?.items ?? [], [entityTagQuery.data?.items])

  const availableTags = useMemo((): Tag[] => {
    const attachedIds = new Set(attachedTags.map((tag) => String(tag.id)))
    return (tagListQuery.data?.items ?? []).filter((tag) => !attachedIds.has(String(tag.id)))
  }, [attachedTags, tagListQuery.data?.items])

  const attachTag = useCallback(async () => {
    if (selectedTagId === '') {
      return
    }

    await attachMutation.mutateAsync({
      entityId,
      tagId: toTagId(Number(selectedTagId)),
    })
    setSelectedTagId('')
  }, [attachMutation, entityId, selectedTagId])

  const detachTag = useCallback(
    async (tag: EntityTag) => {
      await detachMutation.mutateAsync({
        entityId,
        tagId: tag.id,
      })
    },
    [detachMutation, entityId],
  )

  const isLoading = entityTagQuery.isLoading || tagListQuery.isLoading
  const isError = entityTagQuery.isError || tagListQuery.isError
  const errorTitle = entityTagQuery.error?.title ?? tagListQuery.error?.title ?? null

  return {
    attachedTags,
    availableTags,
    selectedTagId,
    setSelectedTagId,
    isLoading,
    isError,
    errorTitle,
    isAttaching: attachMutation.isPending,
    attachErrorTitle: attachMutation.error?.title ?? null,
    isDetaching: detachMutation.isPending,
    detachErrorTitle: detachMutation.error?.title ?? null,
    attachTag,
    detachTag,
    refetch: async () => {
      await Promise.all([entityTagQuery.refetch(), tagListQuery.refetch()])
    },
  }
}
