import { useCallback, useState } from 'react'
import { useEntityTypeList } from '@/entities/entity-type'
import type { NavLocation } from '@/entities/navigation-item'
import {
  useCreateWidget,
  useDeleteWidget,
  useUpdateWidget,
  useWidgetList,
  type Widget,
  type WidgetInput,
  type WidgetType,
} from '@/entities/widget'
import type { ContentRegion } from '@/shared/lib/resolve-layout'

export interface WidgetFormState {
  widgetType: WidgetType
  region: ContentRegion
  title: string
  entityTypeSlug: string
  limit: number
  menuLocation: NavLocation
}

const EMPTY_FORM: WidgetFormState = {
  widgetType: 'recent-posts',
  region: 'sidebar',
  title: '',
  entityTypeSlug: '',
  limit: 5,
  menuLocation: 'side',
}

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
      widgetType: widget.widgetType,
      region: widget.region,
      title: widget.title ?? '',
      entityTypeSlug:
        typeof widget.settings['entityTypeSlug'] === 'string'
          ? widget.settings['entityTypeSlug']
          : '',
      limit: typeof widget.settings['limit'] === 'number' ? widget.settings['limit'] : 5,
      menuLocation:
        widget.settings['location'] === 'header' ||
        widget.settings['location'] === 'footer' ||
        widget.settings['location'] === 'side'
          ? widget.settings['location']
          : 'side',
    })
  }, [])

  const submit = useCallback(async () => {
    const settings =
      form.widgetType === 'menu'
        ? { location: form.menuLocation }
        : form.widgetType === 'toc'
          ? {}
          : { entityTypeSlug: form.entityTypeSlug, limit: form.limit }
    const input: WidgetInput = {
      widgetType: form.widgetType,
      region: form.region,
      displayOrder: 0,
      title: form.title.trim() === '' ? null : form.title.trim(),
      settings,
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
