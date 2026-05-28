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
  // Email-change verification (#283). Token values drive the response:
  //   'valid'   → 204, 'expired' → 410, anything else → 422.
  http.post('/api/v1/auth/verify-email', async ({ request }) => {
    const body = (await request.json().catch(() => ({}))) as { token?: unknown }
    const token = typeof body.token === 'string' ? body.token : ''

    if (token === 'valid') {
      return new HttpResponse(null, { status: 204 })
    }

    if (token === 'expired') {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/gone',
          title: 'Gone',
          status: 410,
          detail: 'Email verification token has expired.',
        },
        { status: 410 },
      )
    }

    return HttpResponse.json(
      {
        type: 'https://nene-records.dev/problems/invalid-token',
        title: 'Unprocessable Entity',
        status: 422,
        detail: 'Email verification token is invalid.',
      },
      { status: 422 },
    )
  }),
]
