import { http, HttpResponse } from 'msw'

/** Default: nothing resolves (tests opt in by overriding when needed). #656 */
export const publicPermalinkResolveHandlers = [
  http.get('/api/v1/public/records/resolve', () => HttpResponse.json({ found: false })),
]
