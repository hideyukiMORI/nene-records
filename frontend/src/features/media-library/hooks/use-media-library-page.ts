import { useCallback, useRef, useState } from 'react'
import { useDeleteMedia, useMediaList, useUploadMedia, type Media } from '@/entities/media'

export function useMediaLibraryPage() {
  const listQuery = useMediaList()
  const uploadMutation = useUploadMedia()
  const deleteMutation = useDeleteMedia()

  const [deleteTarget, setDeleteTarget] = useState<Media | null>(null)
  const [copiedId, setCopiedId] = useState<number | null>(null)
  const copyTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null)

  const uploadFiles = useCallback(
    async (files: FileList | File[]) => {
      const fileArray = Array.from(files)
      for (const file of fileArray) {
        await uploadMutation.mutateAsync(file)
      }
    },
    [uploadMutation],
  )

  const copyUrl = useCallback((media: Media) => {
    void navigator.clipboard.writeText(media.url).then(() => {
      setCopiedId(media.id)
      if (copyTimeoutRef.current !== null) {
        clearTimeout(copyTimeoutRef.current)
      }
      copyTimeoutRef.current = setTimeout(() => {
        setCopiedId(null)
      }, 2000)
    })
  }, [])

  const requestDelete = useCallback((media: Media) => {
    setDeleteTarget(media)
  }, [])

  const cancelDelete = useCallback(() => {
    setDeleteTarget(null)
  }, [])

  const confirmDelete = useCallback(async () => {
    if (deleteTarget === null) return
    await deleteMutation.mutateAsync(deleteTarget.id)
    setDeleteTarget(null)
  }, [deleteMutation, deleteTarget])

  return {
    items: listQuery.data?.items ?? [],
    isLoading: listQuery.isLoading,
    isError: listQuery.isError,
    errorTitle: listQuery.error?.title ?? null,
    refetch: listQuery.refetch,

    uploadFiles,
    isUploading: uploadMutation.isPending,
    uploadErrorTitle: uploadMutation.error?.title ?? null,

    copiedId,
    copyUrl,

    deleteTarget,
    requestDelete,
    cancelDelete,
    confirmDelete,
    isDeleting: deleteMutation.isPending,
  }
}
