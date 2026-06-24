import { http, HttpResponse } from 'msw'

/**
 * POST /api/v1/migration/wxr — preview (dry_run=true) returns a plan; execute
 * (dry_run=false) returns a result. Mirrors the backend WxrImportHttpHandler.
 */
export const wxrImportHandlers = [
  http.post('/api/v1/migration/wxr', async ({ request }) => {
    const form = await request.formData()

    if (form.get('file') === null) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/wxr-no-file',
          title: 'No WXR File',
          status: 422,
          instance: '/api/v1/migration/wxr',
        },
        { status: 422 },
      )
    }

    const dryRun = form.get('dry_run') !== 'false'

    if (dryRun) {
      return HttpResponse.json({
        mode: 'preview',
        planned_count: 4,
        skipped_count: 1,
        counts_by_entity_type: { posts: 3, pages: 1 },
        counts_by_status: { published: 3, draft: 1 },
        tags: ['news', 'php'],
        warnings: ['「No Slug Here」は slug が無いためタイトルから生成します: no-slug-here'],
        planned: [
          {
            title: 'Hello World',
            slug: 'hello-world',
            entity_type: 'posts',
            status: 'published',
            tags: ['news', 'php'],
          },
          { title: 'About', slug: 'about', entity_type: 'pages', status: 'published', tags: [] },
        ],
        skipped: [{ title: 'image.jpg', reason: '未対応の post_type: attachment' }],
      })
    }

    return HttpResponse.json(
      {
        mode: 'import',
        created_entities: 4,
        skipped_existing: 0,
        tags_ensured: 2,
        tag_links: 2,
        redirects_created: 2,
        media_imported: 1,
        media_skipped: 0,
        skipped: [{ title: 'image.jpg', reason: '未対応の post_type: attachment' }],
        warnings: [],
      },
      { status: 201 },
    )
  }),
]
