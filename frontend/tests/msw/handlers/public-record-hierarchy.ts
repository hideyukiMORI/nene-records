import { http, HttpResponse } from 'msw'

/** Default: a flat record with no derived hierarchy (#651 PR2). */
export const publicRecordHierarchyHandlers = [
  http.get('/api/v1/public/records/:id/hierarchy', () =>
    HttpResponse.json({ breadcrumbs: [], childPages: [] }),
  ),
]
