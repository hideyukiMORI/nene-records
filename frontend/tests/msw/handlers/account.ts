import { http, HttpResponse } from 'msw'

/** A free-plan tenant: custom domain blocked, capped limits, some usage. */
export const accountHandlers = [
  http.get('/api/v1/account', () =>
    HttpResponse.json({
      slug: 'my-shop',
      name: 'My Shop',
      plan: 'free',
      custom_domain: null,
      entitlements: {
        custom_domain_allowed: false,
        max_records: 1000,
        max_storage_bytes: 1073741824,
        max_admin_users: 1,
      },
      usage: { records: 12 },
    }),
  ),
]
