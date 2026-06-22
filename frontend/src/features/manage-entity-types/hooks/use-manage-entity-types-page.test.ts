import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest'
import { act, waitFor } from '@testing-library/react'
import { useManageEntityTypesPage } from './use-manage-entity-types-page'
import { resetEntityTypeStore, seedEntityTypes } from '@tests/msw/handlers/entity-type'
import { fieldDefStoreSnapshot, resetFieldDefStore } from '@tests/msw/handlers/field-def'
import { mswServer } from '@tests/msw/server'
import { renderHookWithProviders } from '@tests/render/render-with-providers'

describe('useManageEntityTypesPage', () => {
  beforeAll(() => {
    mswServer.listen()
  })
  afterEach(() => {
    mswServer.resetHandlers()
    resetEntityTypeStore()
    resetFieldDefStore()
  })
  afterAll(() => {
    mswServer.close()
  })

  it('loads the seeded entity types', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])

    const { result } = renderHookWithProviders(() => useManageEntityTypesPage())

    await waitFor(() => {
      expect(result.current.items).toHaveLength(1)
    })
    expect(result.current.items[0]?.name).toBe('Article')
  })

  it('creates an entity type which appears in the list', async () => {
    seedEntityTypes([])

    const { result } = renderHookWithProviders(() => useManageEntityTypesPage())
    await waitFor(() => {
      expect(result.current.items).toHaveLength(0)
    })

    await act(async () => {
      await result.current.createEntityType({
        name: 'Page',
        slug: 'page',
        isPinned: false,
        starter: 'blank',
      })
    })

    await waitFor(() => {
      expect(result.current.items.map((t) => t.slug)).toContain('page')
    })
  })

  it('provisions a title + blocks body when the rich_page starter is chosen', async () => {
    seedEntityTypes([])

    const { result } = renderHookWithProviders(() => useManageEntityTypesPage())
    await waitFor(() => {
      expect(result.current.items).toHaveLength(0)
    })

    await act(async () => {
      await result.current.createEntityType({
        name: 'Landing',
        slug: 'landing',
        isPinned: false,
        starter: 'rich_page',
      })
    })

    await waitFor(() => {
      expect(result.current.items.map((t) => t.slug)).toContain('landing')
    })

    const created = result.current.items.find((t) => t.slug === 'landing')
    const fields = fieldDefStoreSnapshot().filter((f) => f.entity_type_id === created?.id)
    expect(fields.map((f) => f.field_key)).toEqual(['title', 'content'])
    expect(fields.find((f) => f.field_key === 'content')?.data_type).toBe('blocks')
  })

  it('deletes the targeted entity type', async () => {
    seedEntityTypes([
      { id: 1, name: 'Article', slug: 'article' },
      { id: 2, name: 'Page', slug: 'page' },
    ])

    const { result } = renderHookWithProviders(() => useManageEntityTypesPage())
    await waitFor(() => {
      expect(result.current.items).toHaveLength(2)
    })

    const target = result.current.items[0]
    if (target === undefined) {
      throw new Error('expected at least one entity type')
    }
    act(() => {
      result.current.requestDelete(target)
    })
    expect(result.current.deleteTarget?.slug).toBe('article')

    await act(async () => {
      await result.current.confirmDelete()
    })

    await waitFor(() => {
      expect(result.current.items).toHaveLength(1)
    })
    expect(result.current.items[0]?.slug).toBe('page')
  })
})
