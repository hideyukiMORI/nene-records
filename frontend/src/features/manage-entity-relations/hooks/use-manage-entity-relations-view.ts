import { useMemo } from 'react'
import {
  defaultFieldDefListParams,
  isRelationFieldDef,
  useFieldDefList,
  type RelationFieldDef,
} from '@/entities/field-def'

export interface ManageEntityRelationsViewState {
  relationFieldDefs: RelationFieldDef[]
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
}

export function useManageEntityRelationsView(entityTypeId: number): ManageEntityRelationsViewState {
  const fieldDefQuery = useFieldDefList(defaultFieldDefListParams(entityTypeId))

  const relationFieldDefs = useMemo(
    () => (fieldDefQuery.data?.items ?? []).filter(isRelationFieldDef),
    [fieldDefQuery.data?.items],
  )

  return {
    relationFieldDefs,
    isLoading: fieldDefQuery.isLoading,
    isError: fieldDefQuery.isError,
    errorTitle: fieldDefQuery.error?.title ?? null,
  }
}
