import { http, HttpResponse } from 'msw'

interface CommentRecord {
  id: number
  entity_id: number
  author_name: string
  body: string
  is_approved: boolean
  created_at: string
}

let nextId = 1
let comments: CommentRecord[] = []

export function resetCommentStore(): void {
  nextId = 1
  comments = []
}

export function seedComments(seed: CommentRecord[]): void {
  comments = [...seed]
  nextId = Math.max(0, ...seed.map((c) => c.id)) + 1
}

export const commentHandlers = [
  // Public list: approved comments for an entity.
  http.get('/api/v1/entities/:id/comments', ({ params }) => {
    const entityId = Number(params.id)
    const items = comments
      .filter((c) => c.entity_id === entityId && c.is_approved)
      .map(({ id, entity_id, author_name, body, is_approved, created_at }) => ({
        id,
        entity_id,
        author_name,
        body,
        is_approved,
        created_at,
      }))

    return HttpResponse.json({ items })
  }),
  // Public submit: stored pending moderation (is_approved=false).
  http.post('/api/v1/entities/:id/comments', async ({ params, request }) => {
    const entityId = Number(params.id)
    const body = (await request.json()) as {
      author_name?: string
      author_email?: string
      body?: string
      website?: string
    }

    // Honeypot — a filled hidden field is rejected.
    if (typeof body.website === 'string' && body.website.trim() !== '') {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/validation-failed',
          title: 'Validation failed',
          status: 422,
          instance: `/api/v1/entities/${String(entityId)}/comments`,
          errors: [{ field: 'website', message: 'Spam detected.', code: 'spam' }],
        },
        { status: 422 },
      )
    }

    const created: CommentRecord = {
      id: nextId++,
      entity_id: entityId,
      author_name: body.author_name ?? '',
      body: body.body ?? '',
      is_approved: false,
      created_at: new Date().toISOString(),
    }
    comments = [...comments, created]

    return HttpResponse.json(
      {
        id: created.id,
        entity_id: created.entity_id,
        author_name: created.author_name,
        body: created.body,
        is_approved: created.is_approved,
        created_at: created.created_at,
      },
      { status: 201 },
    )
  }),
]
