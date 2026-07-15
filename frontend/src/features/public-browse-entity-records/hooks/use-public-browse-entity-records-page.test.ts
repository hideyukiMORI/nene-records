import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest'
import { waitFor } from '@testing-library/react'
import { usePublicBrowseEntityRecordsPage } from './use-public-browse-entity-records-page'
import { resetEntityStore, seedEntities } from '@tests/msw/handlers/entity'
import { resetEntityTypeStore, seedEntityTypes } from '@tests/msw/handlers/entity-type'
import { resetTextFieldStore, seedTextFields } from '@tests/msw/handlers/text-field'
import { mswServer } from '@tests/msw/server'
import { renderHookWithProviders } from '@tests/render/render-with-providers'

const TYPE_ID = 7

function seedType(): void {
  seedEntityTypes([{ id: TYPE_ID, name: 'Pages', slug: 'pages', permalink_pattern: '/{slug}' }])
}

function published(id: number, slug: string, metaTitle: string | null) {
  return {
    id,
    entity_type_id: TYPE_ID,
    slug,
    status: 'published' as const,
    published_at: '2026-07-11T00:00:00Z',
    is_deleted: false,
    deleted_at: null,
    meta_title: metaTitle,
  }
}

describe('usePublicBrowseEntityRecordsPage — label parity with the SSR archive', () => {
  beforeAll(() => {
    mswServer.listen()
  })
  afterEach(() => {
    mswServer.resetHandlers()
    resetEntityStore()
    resetEntityTypeStore()
    resetTextFieldStore()
  })
  afterAll(() => {
    mswServer.close()
  })

  /**
   * The PHP twin is `RecordDisplayLabel::resolve` (title → meta_title → derived
   * excerpt → fallback), consumed by GetPublicTypeArchiveUseCase. These cases pin
   * each rung so the SPA listing cannot drift from what crawlers were served.
   */

  it('prefers the title field, exactly like the SSR archive', async () => {
    seedType()
    seedEntities([published(1, 'a', 'meta title')])
    seedTextFields([{ id: 1, entity_id: 1, field_key: 'title', value: '『文学論』序' }])

    const { result } = renderHookWithProviders(() => usePublicBrowseEntityRecordsPage('pages', 0))

    await waitFor(() => {
      expect(result.current.items).toHaveLength(1)
    })
    expect(result.current.items[0]?.label).toBe('『文学論』序')
  })

  it('falls back to meta_title when the record has no title field (#891)', async () => {
    // A bespoke page: one html field, no `title`. Without meta_title the derived
    // excerpt dumps the shared header/nav, so every row reads the same (#853).
    seedType()
    seedEntities([published(1, 'privacy', 'プライバシーポリシー｜彩音インターナショナル株式会社')])
    seedTextFields([
      {
        id: 1,
        entity_id: 1,
        field_key: 'content',
        value: '<header><nav><a href="/services">サービスと料金</a></nav></header><h1>本文</h1>',
      },
    ])

    const { result } = renderHookWithProviders(() => usePublicBrowseEntityRecordsPage('pages', 0))

    await waitFor(() => {
      expect(result.current.items).toHaveLength(1)
    })
    expect(result.current.items[0]?.label).toBe(
      'プライバシーポリシー｜彩音インターナショナル株式会社',
    )
  })

  it('does not let two bespoke pages that share one html field collapse to the same label', async () => {
    // The production symptom of #891: 12 AYANE pages all listed as
    // "SYSTEM DEV / WEB APP / 受託ソフトウェア開発 …" — their common nav markup.
    const shared = '<header><nav>SYSTEM DEV / WEB APP / 受託ソフトウェア開発</nav></header>'
    seedType()
    seedEntities([
      published(1, 'privacy', 'プライバシーポリシー'),
      published(2, 'trust', 'セキュリティと品質'),
    ])
    seedTextFields([
      { id: 1, entity_id: 1, field_key: 'content', value: `${shared}<p>privacy body</p>` },
      { id: 2, entity_id: 2, field_key: 'content', value: `${shared}<p>trust body</p>` },
    ])

    const { result } = renderHookWithProviders(() => usePublicBrowseEntityRecordsPage('pages', 0))

    await waitFor(() => {
      expect(result.current.items).toHaveLength(2)
    })
    const labels = result.current.items.map((item) => item.label)
    expect(new Set(labels).size).toBe(2)
    expect(labels).not.toContain('SYSTEM DEV / WEB APP / 受託ソフトウェア開発')
  })

  it('uses meta_title when the title field is outside the fetched text-field window (#892)', async () => {
    // The listing shows 20 records id-desc while text fields are fetched
    // {limit:100, offset:0}; on a large type the window holds none of them, which
    // is why aozora/work listed "Record #1115". meta_title keeps the label right.
    seedType()
    seedEntities([published(1115, 'w1115', '『文学論』序')])
    seedTextFields([{ id: 1, entity_id: 999, field_key: 'title', value: 'some other record' }])

    const { result } = renderHookWithProviders(() => usePublicBrowseEntityRecordsPage('pages', 0))

    await waitFor(() => {
      expect(result.current.items).toHaveLength(1)
    })
    expect(result.current.items[0]?.label).toBe('『文学論』序')
    expect(result.current.items[0]?.label).not.toBe('Record #1115')
  })

  it('falls back to Record #id only when nothing resolves', async () => {
    seedType()
    seedEntities([published(42, 'empty', null)])

    const { result } = renderHookWithProviders(() => usePublicBrowseEntityRecordsPage('pages', 0))

    await waitFor(() => {
      expect(result.current.items).toHaveLength(1)
    })
    expect(result.current.items[0]?.label).toBe('Record #42')
  })
})
