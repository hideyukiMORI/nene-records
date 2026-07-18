import { QueryClient } from '@tanstack/react-query'
import { describe, expect, it } from 'vitest'
import type { EntityDto } from '@/entities/entity/api-types'
import { toEntityId } from '@/entities/entity/ids'
import { mapEntityDtoToModel } from '@/entities/entity/mapper'
import { entityKeys } from '@/entities/entity/query-keys'
import { entityRelationKeys } from '@/entities/entity-relation/query-keys'
import { entityTypeKeys } from '@/entities/entity-type/query-keys'
import { boolFieldKeys } from '@/entities/bool-field/query-keys'
import { dateTimeFieldKeys } from '@/entities/datetime-field/query-keys'
import { enumFieldKeys } from '@/entities/enum-field/query-keys'
import { fieldDefKeys } from '@/entities/field-def/query-keys'
import { intFieldKeys } from '@/entities/int-field/query-keys'
import { settingKeys } from '@/entities/setting/query-keys'
import { textFieldKeys } from '@/entities/text-field/query-keys'
import { publicPermalinkResolveKeys } from '@/shared/lib/public-permalink-resolve'
import type { PublicRecordBootstrapDto } from '@/shared/lib/public-record-bootstrap'
import {
  EMPTY_PUBLIC_RECORD_HIERARCHY,
  publicRecordHierarchyKeys,
} from '@/shared/lib/public-record-hierarchy'
import { seedPublicRecordViewCache } from './seed-public-record-view-cache'

const ENTITY_ID = 11
const ENTITY_TYPE_ID = 3

const entityDto: EntityDto = {
  id: ENTITY_ID,
  entity_type_id: ENTITY_TYPE_ID,
  slug: 'company',
  permalink: '/company',
  layout: 'bare',
  status: 'published',
  published_at: '2026-07-01 00:00:00',
  scheduled_at: null,
  is_deleted: false,
  deleted_at: null,
  meta_title: '会社案内',
  meta_description: null,
  created_at: '2026-07-01 00:00:00',
  updated_at: '2026-07-01 00:00:00',
}

function emptyList() {
  return { items: [], limit: 100, offset: 0 }
}

function bootstrap(overrides: Partial<PublicRecordBootstrapDto> = {}): PublicRecordBootstrapDto {
  return {
    entityTypeSlug: 'pages',
    entityTypeId: ENTITY_TYPE_ID,
    entityId: ENTITY_ID,
    entityTypes: emptyList(),
    entity: entityDto,
    fieldDefs: { items: [], limit: 20, offset: 0 },
    textFields: emptyList(),
    intFields: emptyList(),
    enumFields: emptyList(),
    boolFields: emptyList(),
    dateTimeFields: emptyList(),
    entityRelations: [
      { fieldKey: 'related', items: [{ field_key: 'related', target_entity_id: 42 }] },
    ],
    relationTextFieldsByEntityTypeId: { '7': emptyList() },
    ...overrides,
  }
}

describe('seedPublicRecordViewCache', () => {
  it('leaves the cache untouched when there is no bootstrap', () => {
    const queryClient = new QueryClient()
    seedPublicRecordViewCache(queryClient, null)
    expect(queryClient.getQueryCache().getAll()).toHaveLength(0)
  })

  it('seeds every query the record view reads, under the exact keys (#883)', () => {
    // #883 の教訓: seed はキーが1文字でもずれると黙って空振りし、SPA が再フェッチ
    // して Loading を描く。ここでは「どのキーに何が入るか」を完全列挙で固定する。
    const queryClient = new QueryClient()
    seedPublicRecordViewCache(queryClient, bootstrap())

    // 型一覧は limit:100（#883 ①: 消費側 {limit:100} と seed {limit:20} の不一致が原因だった）
    expect(queryClient.getQueryData(entityTypeKeys.list({ limit: 100, offset: 0 }))).toBeDefined()
    // fieldDefs だけは limit:20
    expect(
      queryClient.getQueryData(
        fieldDefKeys.list({ entityTypeId: ENTITY_TYPE_ID, limit: 20, offset: 0 }),
      ),
    ).toBeDefined()

    expect(queryClient.getQueryData(entityKeys.detail(toEntityId(ENTITY_ID)))).toEqual(
      mapEntityDtoToModel(entityDto),
    )

    const fieldParams = { entityId: ENTITY_ID, limit: 100, offset: 0 }
    for (const keys of [
      textFieldKeys,
      intFieldKeys,
      enumFieldKeys,
      boolFieldKeys,
      dateTimeFieldKeys,
    ]) {
      expect(queryClient.getQueryData(keys.list(fieldParams))).toEqual({
        items: [],
        limit: 100,
        offset: 0,
      })
    }

    expect(queryClient.getQueryData(entityRelationKeys.list(ENTITY_ID, 'related'))).toEqual({
      items: [{ fieldKey: 'related', targetEntityId: 42 }],
    })

    // relationTextFieldsByEntityTypeId の文字列キーは数値 entityTypeId に変換して seed される
    expect(
      queryClient.getQueryData(textFieldKeys.list({ entityTypeId: 7, limit: 100, offset: 0 })),
    ).toEqual({ items: [], limit: 100, offset: 0 })

    // 総数 = 上で列挙した 11 本（型一覧/entity/hierarchy/fieldDefs/値5種/relation1/relation-text1）
    expect(queryClient.getQueryCache().getAll()).toHaveLength(11)
  })

  it('defaults the hierarchy to EMPTY so the view never waits on it', () => {
    const queryClient = new QueryClient()
    seedPublicRecordViewCache(queryClient, bootstrap())
    expect(queryClient.getQueryData(publicRecordHierarchyKeys.detail(ENTITY_ID))).toEqual(
      EMPTY_PUBLIC_RECORD_HIERARCHY,
    )
  })

  it('seeds public settings only when the bootstrap carries them', () => {
    const bare = new QueryClient()
    seedPublicRecordViewCache(bare, bootstrap())
    expect(bare.getQueryData(settingKeys.publicList())).toBeUndefined()

    const withSettings = new QueryClient()
    seedPublicRecordViewCache(withSettings, bootstrap({ publicSettings: { items: [] } }))
    expect(withSettings.getQueryData(settingKeys.publicList())).toBeDefined()
  })

  it('seeds the permalink resolution for the served path (#881) and skips it when absent', () => {
    // #881 の教訓: これが無いと SPA は自分が既に持っているレコードを /resolve に
    // 聞き直し、bare ページの上にテーマ chrome の Loading を ~400ms ちらつかせる。
    const seeded = new QueryClient()
    seedPublicRecordViewCache(seeded, bootstrap({ canonicalPath: '/company' }))
    expect(seeded.getQueryData(publicPermalinkResolveKeys.byPath('/company'))).toEqual({
      found: true,
      entityId: ENTITY_ID,
      entityTypeSlug: 'pages',
    })

    const noPath = new QueryClient()
    seedPublicRecordViewCache(noPath, bootstrap({ canonicalPath: '' }))
    expect(noPath.getQueryData(publicPermalinkResolveKeys.byPath(''))).toBeUndefined()
  })
})
