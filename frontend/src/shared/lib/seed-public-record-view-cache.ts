import type { QueryClient } from '@tanstack/react-query'
import type { BoolFieldListDto } from '@/entities/bool-field/api-types'
import { mapBoolFieldListDtoToModel } from '@/entities/bool-field/mapper'
import { boolFieldKeys } from '@/entities/bool-field/query-keys'
import type { DateTimeFieldListDto } from '@/entities/datetime-field/api-types'
import { mapDateTimeFieldListDtoToModel } from '@/entities/datetime-field/mapper'
import { dateTimeFieldKeys } from '@/entities/datetime-field/query-keys'
import type { EnumFieldListDto } from '@/entities/enum-field/api-types'
import { mapEnumFieldListDtoToModel } from '@/entities/enum-field/mapper'
import { enumFieldKeys } from '@/entities/enum-field/query-keys'
import type { EntityDto } from '@/entities/entity/api-types'
import { mapEntityDtoToModel } from '@/entities/entity/mapper'
import { toEntityId } from '@/entities/entity/ids'
import { entityKeys } from '@/entities/entity/query-keys'
import { mapEntityRelationListDtoToModel } from '@/entities/entity-relation/mapper'
import { entityRelationKeys } from '@/entities/entity-relation/query-keys'
import type { EntityTypeListDto } from '@/entities/entity-type/api-types'
import { mapEntityTypeListDtoToModel } from '@/entities/entity-type/mapper'
import { entityTypeKeys } from '@/entities/entity-type/query-keys'
import type { FieldDefListDto } from '@/entities/field-def/api-types'
import { mapFieldDefListDtoToModel } from '@/entities/field-def/mapper'
import { fieldDefKeys } from '@/entities/field-def/query-keys'
import type { IntFieldListDto } from '@/entities/int-field/api-types'
import { mapIntFieldListDtoToModel } from '@/entities/int-field/mapper'
import { intFieldKeys } from '@/entities/int-field/query-keys'
import { mapPublicSettingListDtoToModel } from '@/entities/setting/mapper'
import { settingKeys } from '@/entities/setting/query-keys'
import type { TextFieldListDto } from '@/entities/text-field/api-types'
import { mapTextFieldListDtoToModel } from '@/entities/text-field/mapper'
import { textFieldKeys } from '@/entities/text-field/query-keys'
import {
  readPublicRecordBootstrap,
  type PublicRecordBootstrapDto,
} from '@/shared/lib/public-record-bootstrap'
import {
  EMPTY_PUBLIC_RECORD_HIERARCHY,
  publicRecordHierarchyKeys,
} from '@/shared/lib/public-record-hierarchy'

const ENTITY_TYPE_LIST_PARAMS = { limit: 100, offset: 0 } as const
const FIELD_DEF_LIST_PARAMS = { limit: 20, offset: 0 } as const
const FIELD_VALUE_LIST_PARAMS = { limit: 100, offset: 0 } as const

export function seedPublicRecordViewCache(
  queryClient: QueryClient,
  bootstrap: PublicRecordBootstrapDto | null = readPublicRecordBootstrap(),
): void {
  if (bootstrap === null) {
    return
  }

  queryClient.setQueryData(
    entityTypeKeys.list(ENTITY_TYPE_LIST_PARAMS),
    mapEntityTypeListDtoToModel(bootstrap.entityTypes as EntityTypeListDto),
  )

  queryClient.setQueryData(
    entityKeys.detail(toEntityId(bootstrap.entityId)),
    mapEntityDtoToModel(bootstrap.entity as EntityDto),
  )

  queryClient.setQueryData(
    publicRecordHierarchyKeys.detail(bootstrap.entityId),
    bootstrap.hierarchy ?? EMPTY_PUBLIC_RECORD_HIERARCHY,
  )

  queryClient.setQueryData(
    fieldDefKeys.list({
      entityTypeId: bootstrap.entityTypeId,
      ...FIELD_DEF_LIST_PARAMS,
    }),
    mapFieldDefListDtoToModel(bootstrap.fieldDefs as FieldDefListDto),
  )

  const fieldListParams = { ...FIELD_VALUE_LIST_PARAMS, entityId: bootstrap.entityId }

  queryClient.setQueryData(
    textFieldKeys.list(fieldListParams),
    mapTextFieldListDtoToModel(bootstrap.textFields as TextFieldListDto),
  )
  queryClient.setQueryData(
    intFieldKeys.list(fieldListParams),
    mapIntFieldListDtoToModel(bootstrap.intFields as IntFieldListDto),
  )
  queryClient.setQueryData(
    enumFieldKeys.list(fieldListParams),
    mapEnumFieldListDtoToModel(bootstrap.enumFields as EnumFieldListDto),
  )
  queryClient.setQueryData(
    boolFieldKeys.list(fieldListParams),
    mapBoolFieldListDtoToModel(bootstrap.boolFields as BoolFieldListDto),
  )
  queryClient.setQueryData(
    dateTimeFieldKeys.list(fieldListParams),
    mapDateTimeFieldListDtoToModel(bootstrap.dateTimeFields as DateTimeFieldListDto),
  )

  for (const relation of bootstrap.entityRelations) {
    queryClient.setQueryData(
      entityRelationKeys.list(bootstrap.entityId, relation.fieldKey),
      mapEntityRelationListDtoToModel({ items: relation.items }),
    )
  }

  for (const [entityTypeId, payload] of Object.entries(
    bootstrap.relationTextFieldsByEntityTypeId,
  )) {
    queryClient.setQueryData(
      textFieldKeys.list({
        entityTypeId: Number(entityTypeId),
        ...FIELD_VALUE_LIST_PARAMS,
      }),
      mapTextFieldListDtoToModel(payload as TextFieldListDto),
    )
  }

  if (bootstrap.publicSettings !== undefined) {
    queryClient.setQueryData(
      settingKeys.publicList(),
      mapPublicSettingListDtoToModel(bootstrap.publicSettings),
    )
  }
}
