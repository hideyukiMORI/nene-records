import { http, HttpResponse } from 'msw'

interface LoginBody {
  email?: unknown
  password?: unknown
}

export const authHandlers = [
  http.post('/api/v1/auth/login', async ({ request }) => {
    const body = (await request.json()) as LoginBody

    if (body.email === 'admin@example.com' && body.password === 'secret') {
      return HttpResponse.json({
        token: 'test-token',
        expires_at: new Date(Date.now() + 3600 * 1000).toISOString(),
        email: 'admin@example.com',
        role: 'admin',
      })
    }

    return HttpResponse.json(
      {
        type: 'https://nene-records.dev/problems/invalid-credentials',
        title: 'Invalid credentials',
        status: 401,
        detail: 'Email or password is incorrect.',
      },
      { status: 401 },
    )
  }),
]
