import type { SyntheticEvent } from 'react'
import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest'
import { act, waitFor } from '@testing-library/react'
import { useCommentSection } from './useCommentSection'
import { resetCommentStore, seedComments } from '@tests/msw/handlers/comment'
import { mswServer } from '@tests/msw/server'
import { renderHookWithProviders } from '@tests/render/render-with-providers'

const fakeEvent = { preventDefault: () => {} } as SyntheticEvent

describe('useCommentSection', () => {
  beforeAll(() => {
    mswServer.listen()
  })
  afterEach(() => {
    mswServer.resetHandlers()
    resetCommentStore()
  })
  afterAll(() => {
    mswServer.close()
  })

  it('loads approved comments for the entity', async () => {
    seedComments([
      {
        id: 1,
        entity_id: 7,
        author_name: 'Alice',
        body: 'Nice post',
        is_approved: true,
        created_at: '2026-01-01T00:00:00Z',
      },
      {
        id: 2,
        entity_id: 7,
        author_name: 'Bob',
        body: 'Pending',
        is_approved: false,
        created_at: '2026-01-01T00:00:00Z',
      },
    ])

    const { result } = renderHookWithProviders(() => useCommentSection(7))

    await waitFor(() => {
      expect(result.current.comments).toHaveLength(1)
    })
    expect(result.current.comments[0].authorName).toBe('Alice')
  })

  it('submits a comment and shows the pending-moderation success state', async () => {
    seedComments([])

    const { result } = renderHookWithProviders(() => useCommentSection(7))

    act(() => {
      result.current.onAuthorNameChange('Carol')
      result.current.onAuthorEmailChange('carol@example.com')
      result.current.onBodyChange('Great write-up!')
    })

    act(() => {
      result.current.onSubmit(fakeEvent)
    })

    await waitFor(() => {
      expect(result.current.submitted).toBe(true)
    })
    // Fields are cleared after a successful submit.
    expect(result.current.authorName).toBe('')
    expect(result.current.isPostError).toBe(false)
  })

  it('surfaces an error when the honeypot is filled', async () => {
    seedComments([])

    const { result } = renderHookWithProviders(() => useCommentSection(7))

    act(() => {
      result.current.onAuthorNameChange('SpamBot')
      result.current.onAuthorEmailChange('spam@example.com')
      result.current.onBodyChange('buy now')
      result.current.onHoneypotChange('http://spam.example')
    })

    act(() => {
      result.current.onSubmit(fakeEvent)
    })

    await waitFor(() => {
      expect(result.current.isPostError).toBe(true)
    })
    expect(result.current.submitted).toBe(false)
  })
})
