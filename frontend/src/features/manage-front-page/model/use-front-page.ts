import { useState } from 'react'
import type { Entity } from '@/entities/entity'
import { toEntityId, useEntity, useEntityList } from '@/entities/entity'
import { useEntityTypeList } from '@/entities/entity-type'
import { useSettingList, useUpdateSetting } from '@/entities/setting'
import { useTranslation } from '@/shared/i18n'
import { useToast } from '@/shared/ui'

const FRONT_PAGE_KEY = 'front_page'
/** Preferred content type to default the picker to (WordPress-style pages). */
const DEFAULT_TYPE_SLUG = 'pages'
const RECORD_LIMIT = 100
const TYPE_LIMIT = 100

export type FrontPageMode = 'latest' | 'page'

export interface FrontPageTypeOption {
  id: number
  name: string
}

export interface FrontPageRecordOption {
  id: number
  label: string
}

export interface FrontPageState {
  mode: FrontPageMode
  setMode: (mode: FrontPageMode) => void
  entityTypeId: number | null
  setEntityTypeId: (id: number | null) => void
  recordId: number | null
  setRecordId: (id: number | null) => void
  search: string
  setSearch: (value: string) => void
  typeOptions: FrontPageTypeOption[]
  recordOptions: FrontPageRecordOption[]
  isLoading: boolean
  isRecordsLoading: boolean
  isSaving: boolean
  isDirty: boolean
  canSave: boolean
  save: () => void
}

/** A concise picker label: the record's meta title, else its slug, else `#id`. */
function recordLabel(entity: Entity): string {
  const title = entity.metaTitle?.trim()
  if (title !== undefined && title !== '') {
    return title
  }

  const slug = entity.slug?.trim()
  if (slug !== undefined && slug !== '') {
    return slug
  }

  return `#${String(entity.id)}`
}

/**
 * Admin controller for the "home page display" setting (#701). Persists the
 * `front_page` setting as either '' (latest feed) or the pinned record's id.
 * The type/record pickers list published records and pre-select the pinned one
 * (its type is resolved from the stored id so the right type shows on load).
 */
export function useFrontPage(): FrontPageState {
  const settingsQuery = useSettingList()
  const typeQuery = useEntityTypeList({ limit: TYPE_LIMIT, offset: 0 })
  const updateSetting = useUpdateSetting()
  const { showToast } = useToast()
  const { t } = useTranslation()

  const stored =
    settingsQuery.data?.items.find((item) => item.settingKey === FRONT_PAGE_KEY)?.value ?? ''
  const storedId = /^\d+$/.test(stored) ? Number(stored) : null

  // Resolve the pinned record so the type/record pickers can pre-select it.
  const storedEntityQuery = useEntity(toEntityId(storedId ?? 0), { enabled: storedId !== null })

  const [mode, setMode] = useState<FrontPageMode>(storedId !== null ? 'page' : 'latest')
  const [recordId, setRecordId] = useState<number | null>(storedId)
  const [entityTypeId, setEntityTypeId] = useState<number | null>(null)
  const [search, setSearch] = useState('')

  // Re-sync mode/record when the persisted value changes (e.g. after a save).
  const [syncedStored, setSyncedStored] = useState(stored)
  if (stored !== syncedStored) {
    setSyncedStored(stored)
    setMode(storedId !== null ? 'page' : 'latest')
    setRecordId(storedId)
  }

  const types = typeQuery.data?.items ?? []
  // Default the type picker to the pinned record's type, else `pages`, else the
  // first type. Applied once — kept null until something resolves.
  const defaultTypeId =
    storedEntityQuery.data?.entityTypeId ??
    types.find((type) => type.slug === DEFAULT_TYPE_SLUG)?.id ??
    types[0]?.id ??
    null
  if (entityTypeId === null && defaultTypeId !== null) {
    setEntityTypeId(defaultTypeId)
  }

  const trimmedSearch = search.trim()
  const recordsQuery = useEntityList(
    {
      entityTypeId: entityTypeId ?? 0,
      limit: RECORD_LIMIT,
      offset: 0,
      status: 'published',
      sortKey: 'published_at',
      sortOrder: 'desc',
      ...(trimmedSearch !== '' ? { q: trimmedSearch } : {}),
    },
    { enabled: mode === 'page' && entityTypeId !== null },
  )

  const typeOptions: FrontPageTypeOption[] = types.map((type) => ({
    id: Number(type.id),
    name: type.name,
  }))

  const recordOptions: FrontPageRecordOption[] = (recordsQuery.data?.items ?? []).map((entity) => ({
    id: Number(entity.id),
    label: recordLabel(entity),
  }))

  // Keep the pinned record visible even when the current type/search doesn't list it.
  const storedEntity = storedEntityQuery.data
  if (
    recordId !== null &&
    !recordOptions.some((option) => option.id === recordId) &&
    storedEntity !== undefined &&
    Number(storedEntity.id) === recordId
  ) {
    recordOptions.unshift({ id: recordId, label: recordLabel(storedEntity) })
  }

  const nextValue = mode === 'latest' ? '' : recordId !== null ? String(recordId) : ''
  const isDirty = nextValue !== stored
  const canSave = isDirty && (mode === 'latest' || recordId !== null) && !updateSetting.isPending

  const save = (): void => {
    updateSetting.mutate(
      { settingKey: FRONT_PAGE_KEY, input: { value: nextValue } },
      {
        onSuccess: () => {
          showToast(t('admin.frontPage.saved'), 'success')
        },
        onError: () => {
          showToast(t('admin.frontPage.saveError'), 'error')
        },
      },
    )
  }

  return {
    mode,
    setMode,
    entityTypeId,
    setEntityTypeId,
    recordId,
    setRecordId,
    search,
    setSearch,
    typeOptions,
    recordOptions,
    isLoading: settingsQuery.isLoading || typeQuery.isLoading,
    isRecordsLoading: recordsQuery.isLoading,
    isSaving: updateSetting.isPending,
    isDirty,
    canSave,
    save,
  }
}
