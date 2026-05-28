import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest'
import { act, waitFor } from '@testing-library/react'
import { useManageUsersPage } from './use-manage-users-page'
import { resetUserStore, seedUsers } from '@tests/msw/handlers/user'
import { mswServer } from '@tests/msw/server'
import { renderHookWithProviders } from '@tests/render/render-with-providers'

interface SeedUser {
  id: number
  email: string
  role: string
}

function seed(list: SeedUser[]): void {
  seedUsers(
    list.map((u) => ({
      id: u.id,
      email: u.email,
      role: u.role,
      organization_id: 1,
      org_role: 'member',
      status: 'active',
      display_name: null,
      full_name: null,
      job_title: null,
      created_at: '2026-01-01T00:00:00Z',
      updated_at: '2026-01-01T00:00:00Z',
    })),
  )
}

describe('useManageUsersPage', () => {
  beforeAll(() => {
    mswServer.listen()
  })
  afterEach(() => {
    mswServer.resetHandlers()
    resetUserStore()
  })
  afterAll(() => {
    mswServer.close()
  })

  it('loads the seeded users', async () => {
    seed([
      { id: 1, email: 'admin@example.com', role: 'admin' },
      { id: 2, email: 'editor@example.com', role: 'editor' },
    ])

    const { result } = renderHookWithProviders(() => useManageUsersPage())

    await waitFor(() => {
      expect(result.current.users).toHaveLength(2)
    })
    expect(result.current.users.map((u) => u.email)).toContain('editor@example.com')
  })

  it('changes a user role and reflects it after refetch', async () => {
    seed([{ id: 3, email: 'editor@example.com', role: 'editor' }])

    const { result } = renderHookWithProviders(() => useManageUsersPage())
    await waitFor(() => {
      expect(result.current.users).toHaveLength(1)
    })

    await act(async () => {
      await result.current.updateRole(3, 'admin')
    })

    await waitFor(() => {
      expect(result.current.users.find((u) => u.id === 3)?.role).toBe('admin')
    })
  })

  it('invites a user, which appears in the list', async () => {
    seed([])

    const { result } = renderHookWithProviders(() => useManageUsersPage())
    await waitFor(() => {
      expect(result.current.users).toHaveLength(0)
    })

    await act(async () => {
      await result.current.inviteUser({ email: 'invitee@example.com', role: 'editor' })
    })

    await waitFor(() => {
      expect(result.current.users.map((u) => u.email)).toContain('invitee@example.com')
    })
    expect(result.current.showInviteForm).toBe(false)
  })

  it('deletes the targeted user', async () => {
    seed([
      { id: 1, email: 'admin@example.com', role: 'admin' },
      { id: 2, email: 'editor@example.com', role: 'editor' },
    ])

    const { result } = renderHookWithProviders(() => useManageUsersPage())
    await waitFor(() => {
      expect(result.current.users).toHaveLength(2)
    })

    act(() => {
      result.current.requestDelete(result.current.users[1])
    })
    expect(result.current.deleteTarget?.id).toBe(2)

    await act(async () => {
      await result.current.confirmDelete()
    })

    await waitFor(() => {
      expect(result.current.users).toHaveLength(1)
    })
    expect(result.current.users[0].email).toBe('admin@example.com')
  })
})
