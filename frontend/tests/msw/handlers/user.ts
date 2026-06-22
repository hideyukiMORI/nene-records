import { http, HttpResponse } from 'msw'

interface UserRecord {
  id: number
  email: string
  role: string
  organization_id: number | null
  org_role: string | null
  status: string
  display_name: string | null
  full_name: string | null
  job_title: string | null
  created_at: string
  updated_at: string
}

let nextId = 4
let users: UserRecord[] = [
  {
    id: 1,
    email: 'superadmin@example.com',
    role: 'superadmin',
    organization_id: 1,
    org_role: 'admin',
    status: 'active',
    display_name: 'Super Admin',
    full_name: null,
    job_title: null,
    created_at: new Date('2026-01-01').toISOString(),
    updated_at: new Date('2026-01-01').toISOString(),
  },
  {
    id: 2,
    email: 'admin@example.com',
    role: 'admin',
    organization_id: 1,
    org_role: 'admin',
    status: 'active',
    display_name: 'Admin User',
    full_name: null,
    job_title: null,
    created_at: new Date('2026-01-02').toISOString(),
    updated_at: new Date('2026-01-02').toISOString(),
  },
  {
    id: 3,
    email: 'editor@example.com',
    role: 'editor',
    organization_id: 1,
    org_role: 'member',
    status: 'active',
    display_name: null,
    full_name: null,
    job_title: null,
    created_at: new Date('2026-01-03').toISOString(),
    updated_at: new Date('2026-01-03').toISOString(),
  },
]

export function resetUserStore(): void {
  nextId = 4
  users = []
}

export function seedUsers(seed: UserRecord[]): void {
  users = seed
  nextId = Math.max(0, ...seed.map((u) => u.id)) + 1
}

function now(): string {
  return new Date().toISOString()
}

function notFound(): Response {
  return HttpResponse.json(
    { type: 'about:blank', title: 'Not Found', status: 404 },
    { status: 404 },
  )
}

export const userHandlers = [
  // List users
  http.get('/api/v1/users', () => {
    return HttpResponse.json({ items: users })
  }),

  // Get user by id
  http.get('/api/v1/users/:id', ({ params }) => {
    const user = users.find((u) => u.id === Number(params.id))
    if (user === undefined) return notFound()
    return HttpResponse.json(user)
  }),

  // Create user
  http.post('/api/v1/users', async ({ request }) => {
    const body = (await request.json()) as { email?: string; password?: string; role?: string }
    const user: UserRecord = {
      id: nextId++,
      email: body.email ?? '',
      role: body.role ?? 'editor',
      organization_id: 1,
      org_role: 'member',
      status: 'active',
      display_name: null,
      full_name: null,
      job_title: null,
      created_at: now(),
      updated_at: now(),
    }
    users.push(user)
    return HttpResponse.json(user, { status: 201 })
  }),

  // Update role
  http.patch('/api/v1/users/:id', async ({ params, request }) => {
    const index = users.findIndex((u) => u.id === Number(params.id))
    if (index === -1) return notFound()
    const body = (await request.json()) as { role?: string }
    const current = users[index]
    if (current === undefined) return notFound()
    const updated = { ...current, role: body.role ?? current.role, updated_at: now() }
    users[index] = updated
    return HttpResponse.json(updated)
  }),

  // Change email
  http.patch('/api/v1/users/:id/email', async ({ params, request }) => {
    const index = users.findIndex((u) => u.id === Number(params.id))
    if (index === -1) return notFound()
    const body = (await request.json()) as { email?: string }
    const current = users[index]
    if (current === undefined) return notFound()
    users[index] = { ...current, email: body.email ?? current.email, updated_at: now() }
    return new HttpResponse(null, { status: 204 })
  }),

  // Update profile
  http.patch('/api/v1/users/:id/profile', async ({ params, request }) => {
    const index = users.findIndex((u) => u.id === Number(params.id))
    if (index === -1) return notFound()
    const body = (await request.json()) as {
      display_name?: string | null
      full_name?: string | null
      job_title?: string | null
    }
    const current = users[index]
    if (current === undefined) return notFound()
    const updated = {
      ...current,
      display_name: body.display_name ?? current.display_name,
      full_name: body.full_name ?? current.full_name,
      job_title: body.job_title ?? current.job_title,
      updated_at: now(),
    }
    users[index] = updated
    return HttpResponse.json({
      user_id: updated.id,
      display_name: updated.display_name,
      full_name: updated.full_name,
      job_title: updated.job_title,
    })
  }),

  // Reset password (admin)
  http.patch('/api/v1/users/:id/password', () => {
    return new HttpResponse(null, { status: 204 })
  }),

  // Change own password
  http.put('/api/v1/users/me/password', () => {
    return new HttpResponse(null, { status: 204 })
  }),

  // Delete user
  http.delete('/api/v1/users/:id', ({ params }) => {
    const index = users.findIndex((u) => u.id === Number(params.id))
    if (index === -1) return notFound()
    users.splice(index, 1)
    return new HttpResponse(null, { status: 204 })
  }),

  // Invite user
  http.post('/api/v1/users/invite', async ({ request }) => {
    const body = (await request.json()) as { email?: string; role?: string }
    const user: UserRecord = {
      id: nextId++,
      email: body.email ?? '',
      role: body.role ?? 'editor',
      organization_id: 1,
      org_role: 'member',
      status: 'invited',
      display_name: null,
      full_name: null,
      job_title: null,
      created_at: now(),
      updated_at: now(),
    }
    users.push(user)
    return HttpResponse.json(user, { status: 201 })
  }),
]
