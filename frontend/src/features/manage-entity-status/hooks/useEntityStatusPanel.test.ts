import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest'
import { act, waitFor } from '@testing-library/react'
import { http, HttpResponse } from 'msw'
import { useEntityStatusPanel } from './useEntityStatusPanel'
import { toEntityId, type Entity } from '@/entities/entity'
import { mswServer } from '@tests/msw/server'
import { renderHookWithProviders } from '@tests/render/render-with-providers'

function makeEntity(overrides: Partial<Entity> = {}): Entity {
  return {
    id: toEntityId(7),
    entityTypeId: 1,
    slug: 'hello',
    permalink: null,
    layout: null,
    status: 'draft',
    publishedAt: null,
    scheduledAt: null,
    isDeleted: false,
    deletedAt: null,
    metaTitle: 'SEO Title',
    metaDescription: 'SEO Description',
    createdAt: null,
    updatedAt: null,
    ...overrides,
  }
}

/** Install a PUT handler that records each update body and echoes a valid EntityDto. */
function captureUpdates(): Array<Record<string, unknown>> {
  const bodies: Array<Record<string, unknown>> = []
  mswServer.use(
    http.put('/api/v1/entities/:id', async ({ request, params }) => {
      const body = (await request.json()) as Record<string, unknown>
      bodies.push(body)
      return HttpResponse.json({
        id: Number(params.id),
        entity_type_id: body.entity_type_id ?? 1,
        slug: body.slug ?? null,
        layout: body.layout ?? null,
        status: body.status ?? 'draft',
        published_at: null,
        scheduled_at: null,
        is_deleted: false,
        deleted_at: null,
        meta_title: body.meta_title ?? null,
        meta_description: body.meta_description ?? null,
        created_at: null,
        updated_at: null,
      })
    }),
  )
  return bodies
}

describe('useEntityStatusPanel — preserves SEO meta on full-replace update', () => {
  beforeAll(() => {
    mswServer.listen()
  })
  afterEach(() => {
    mswServer.resetHandlers()
  })
  afterAll(() => {
    mswServer.close()
  })

  it('onChangeStatus echoes the existing meta_title / meta_description', async () => {
    const bodies = captureUpdates()
    const { result } = renderHookWithProviders(() => useEntityStatusPanel(makeEntity()))

    act(() => {
      result.current.onChangeStatus('published')
    })

    await waitFor(() => {
      expect(bodies).toHaveLength(1)
    })
    expect(bodies[0]).toMatchObject({
      status: 'published',
      meta_title: 'SEO Title',
      meta_description: 'SEO Description',
    })
  })

  it('onSaveSlug echoes the existing meta_title / meta_description', async () => {
    const bodies = captureUpdates()
    const { result } = renderHookWithProviders(() => useEntityStatusPanel(makeEntity()))

    act(() => {
      result.current.onSaveSlug()
    })

    await waitFor(() => {
      expect(bodies).toHaveLength(1)
    })
    expect(bodies[0]).toMatchObject({
      meta_title: 'SEO Title',
      meta_description: 'SEO Description',
    })
  })

  it('onSavePermalink sends the custom permalink while preserving slug + meta (#651)', async () => {
    const bodies = captureUpdates()
    const { result } = renderHookWithProviders(() =>
      useEntityStatusPanel(makeEntity({ permalink: '/company/about/team' })),
    )

    act(() => {
      result.current.onSavePermalink()
    })

    await waitFor(() => {
      expect(bodies).toHaveLength(1)
    })
    expect(bodies[0]).toMatchObject({
      permalink: '/company/about/team',
      slug: 'hello',
      meta_title: 'SEO Title',
    })
  })

  it('onChangeStatus echoes the existing permalink so a status change does not clear it (#651)', async () => {
    const bodies = captureUpdates()
    const { result } = renderHookWithProviders(() =>
      useEntityStatusPanel(makeEntity({ permalink: '/company/about/team' })),
    )

    act(() => {
      result.current.onChangeStatus('published')
    })

    await waitFor(() => {
      expect(bodies).toHaveLength(1)
    })
    expect(bodies[0]).toMatchObject({ permalink: '/company/about/team' })
  })
})
