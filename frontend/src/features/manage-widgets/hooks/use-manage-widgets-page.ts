import { useCallback, useState } from 'react'
import { useEntityTypeList } from '@/entities/entity-type'
import {
  useCreateWidget,
  useDeleteWidget,
  useUpdateWidget,
  useWidgetList,
  type Widget,
  type WidgetInput,
} from '@/entities/widget'
import type { ContentRegion } from '@/shared/lib/resolve-layout'

export interface WidgetFormState {
  region: ContentRegion
  title: string
  entityTypeSlug: string
  limit: number
}

const EMPTY_FORM: WidgetFormState = { region: 'sidebar', title: '', entityTypeSlug: '', limit: 5 }

export function useManageWidgetsPage() {
  const listQuery = useWidgetList()
  const entityTypesQuery = useEntityTypeList()
  const createMutation = useCreateWidget()
  const updateMutation = useUpdateWidget()
  const deleteMutation = useDeleteWidget()

  const [editId, setEditId] = useState<number | null>(null)
  const [form, setForm] = useState<WidgetFormState>(EMPTY_FORM)

  const setField = useCallback(
    <K extends keyof WidgetFormState>(key: K, value: WidgetFormState[K]) => {
      setForm((current) => ({ ...current, [key]: value }))
    },
    [],
  )

  const resetForm = useCallback(() => {
    setEditId(null)
    setForm(EMPTY_FORM)
  }, [])

  const editWidget = useCallback((widget: Widget) => {
    setEditId(widget.id)
    setForm({
      region: widget.region,
      title: widget.title ?? '',
      entityTypeSlug:
        typeof widget.settings['entityTypeSlug'] === 'string'
          ? widget.settings['entityTypeSlug']
          : '',
      limit: typeof widget.settings['limit'] === 'number' ? widget.settings['limit'] : 5,
    })
  }, [])

  const submit = useCallback(async () => {
    const input: WidgetInput = {
      widgetType: 'recent-posts',
      region: form.region,
      displayOrder: 0,
      title: form.title.trim() === '' ? null : form.title.trim(),
      settings: { entityTypeSlug: form.entityTypeSlug, limit: form.limit },
    }
    if (editId !== null) {
      await updateMutation.mutateAsync({ id: editId, input })
    } else {
      await createMutation.mutateAsync(input)
    }
    resetForm()
  }, [createMutation, editId, form, resetForm, updateMutation])

  const remove = useCallback(
    async (id: number) => {
      await deleteMutation.mutateAsync(id)
      if (editId === id) {
        resetForm()
      }
    },
    [deleteMutation, editId, resetForm],
  )

  return {
    widgets: listQuery.data?.items ?? [],
    isLoading: listQuery.isLoading,
    entityTypes: entityTypesQuery.data?.items ?? [],
    form,
    editId,
    isSubmitting: createMutation.isPending || updateMutation.isPending,
    setField,
    resetForm,
    editWidget,
    submit,
    remove,
  }
}
