import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest'
import { act, waitFor } from '@testing-library/react'
import { http, HttpResponse } from 'msw'
import { useEntitySeoPanel } from './useEntitySeoPanel'
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
    showComments: null,
    showRelated: null,
    status: 'draft',
    publishedAt: null,
    scheduledAt: null,
    isDeleted: false,
    deletedAt: null,
    metaTitle: null,
    metaDescription: null,
    menuOrder: 0,
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
        permalink: body.permalink ?? null,
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

describe('useEntitySeoPanel — preserves untouched fields on full-replace update', () => {
  beforeAll(() => {
    mswServer.listen()
  })
  afterEach(() => {
    mswServer.resetHandlers()
  })
  afterAll(() => {
    mswServer.close()
  })

  it('onSave echoes the custom permalink / layout / visibility overrides (#776)', async () => {
    const bodies = captureUpdates()
    const { result } = renderHookWithProviders(() =>
      useEntitySeoPanel(
        makeEntity({
          permalink: '/company/about',
          layout: 'two-col',
          showComments: false,
          showRelated: false,
        }),
      ),
    )

    act(() => {
      result.current.onMetaTitleChange('New title')
    })
    act(() => {
      result.current.onSave()
    })

    await waitFor(() => {
      expect(bodies).toHaveLength(1)
    })
    expect(bodies[0]).toMatchObject({
      meta_title: 'New title',
      permalink: '/company/about',
      layout: 'two-col',
      show_comments: false,
      show_related: false,
    })
  })
})
