import { useMemo } from 'react'
import {
  allFieldDefListParams,
  isRelationFieldDef,
  useFieldDefList,
  type RelationFieldDef,
} from '@/entities/field-def'

export function useInverseRelationFieldDefs(targetEntityTypeId: number) {
  const fieldDefQuery = useFieldDefList(allFieldDefListParams())

  const inverseFieldDefs = useMemo((): RelationFieldDef[] => {
    return (fieldDefQuery.data?.items ?? []).filter(
      (fieldDef): fieldDef is RelationFieldDef =>
        isRelationFieldDef(fieldDef) && fieldDef.targetEntityTypeId === targetEntityTypeId,
    )
  }, [fieldDefQuery.data?.items, targetEntityTypeId])

  return {
    inverseFieldDefs,
    isLoading: fieldDefQuery.isLoading,
    isError: fieldDefQuery.isError,
    errorTitle: fieldDefQuery.error?.title ?? null,
    refetch: async () => {
      await fieldDefQuery.refetch()
    },
  }
}
