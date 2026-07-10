import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest'
import { cleanup, screen } from '@testing-library/react'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import {
  PublicRecordByPermalink,
  PublicRecordDetailPage,
} from '@/pages/consumer/PublicRecordDetailPage'
import { resetBoolFieldStore, seedBoolFields } from '@tests/msw/handlers/bool-field'
import { resetEntityStore, seedEntities } from '@tests/msw/handlers/entity'
import { resetEntityRelationStore, seedEntityRelations } from '@tests/msw/handlers/entity-relation'
import { resetEntityTypeStore, seedEntityTypes } from '@tests/msw/handlers/entity-type'
import { resetFieldDefStore, seedFieldDefs } from '@tests/msw/handlers/field-def'
import { resetIntFieldStore, seedIntFields } from '@tests/msw/handlers/int-field'
import { resetTextFieldStore, seedTextFields } from '@tests/msw/handlers/text-field'
import { mswServer } from '@tests/msw/server'
import { PublicSiteTestProvider } from '@tests/render/PublicSiteTestProvider'
import { renderWithProviders } from '@tests/render/render-with-providers'

function renderDetailPage(
  entityTypeSlug = 'article',
  entityId = 1,
  site?: Parameters<typeof PublicSiteTestProvider>[0]['site'],
) {
  return renderWithProviders(
    <MemoryRouter initialEntries={[`/${entityTypeSlug}/${String(entityId)}`]}>
      <Routes>
        <Route element={<PublicSiteTestProvider {...(site !== undefined ? { site } : {})} />}>
          <Route path="/:entityTypeSlug/*" element={<PublicRecordDetailPage />} />
        </Route>
      </Routes>
    </MemoryRouter>,
  )
}

describe('PublicRecordDetailPage', () => {
  beforeAll(() => {
    mswServer.listen()
  })

  afterEach(() => {
    mswServer.resetHandlers()
    resetEntityTypeStore()
    resetEntityStore()
    resetEntityRelationStore()
    resetFieldDefStore()
    resetTextFieldStore()
    resetIntFieldStore()
    resetBoolFieldStore()
    cleanup()
  })

  afterAll(() => {
    mswServer.close()
  })

  it('renders read-only field values', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    seedEntities([
      {
        id: 1,
        entity_type_id: 1,
        is_deleted: false,
        deleted_at: null,
      },
    ])
    seedFieldDefs([
      { id: 1, entity_type_id: 1, field_key: 'title', data_type: 'text' },
      { id: 2, entity_type_id: 1, field_key: 'views', data_type: 'int' },
      { id: 3, entity_type_id: 1, field_key: 'published', data_type: 'bool' },
    ])
    seedTextFields([
      {
        id: 1,
        entity_id: 1,
        field_key: 'title',
        value: 'My article',
      },
    ])
    seedIntFields([
      {
        id: 1,
        entity_id: 1,
        field_key: 'views',
        value: 42,
      },
    ])
    seedBoolFields([
      {
        id: 1,
        entity_id: 1,
        field_key: 'published',
        value: true,
      },
    ])

    renderDetailPage()

    // The record title is lifted into the masthead h1 (and dropped from the body).
    expect(await screen.findByRole('heading', { level: 1, name: 'My article' })).toBeInTheDocument()
    expect(screen.getByText('views')).toBeInTheDocument()
    expect(screen.getByText('42')).toBeInTheDocument()
    expect(screen.getByText('published')).toBeInTheDocument()
    expect(screen.getByText('Yes')).toBeInTheDocument()
  })

  // ── Comments / related visibility (#775) ─────────────────────────────────
  // Per-record tri-state (show_comments / show_related) wins; null falls back
  // to the site-wide record_page_config default.

  function seedTitledRecord(
    overrides: { show_comments?: boolean | null; show_related?: boolean | null } = {},
  ) {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    seedEntities([{ id: 1, entity_type_id: 1, is_deleted: false, deleted_at: null, ...overrides }])
    seedFieldDefs([{ id: 1, entity_type_id: 1, field_key: 'title', data_type: 'text' }])
    seedTextFields([{ id: 1, entity_id: 1, field_key: 'title', value: 'My article' }])
  }

  it('shows the comments section by default', async () => {
    seedTitledRecord()
    renderDetailPage()

    await screen.findByRole('heading', { level: 1, name: 'My article' })
    expect(screen.getByRole('heading', { name: 'Comments' })).toBeInTheDocument()
  })

  it('hides the comments section when the site default disables it', async () => {
    seedTitledRecord()
    renderDetailPage('article', 1, { recordPageConfig: { comments: false, related: true } })

    await screen.findByRole('heading', { level: 1, name: 'My article' })
    expect(screen.queryByRole('heading', { name: 'Comments' })).not.toBeInTheDocument()
  })

  it('show_comments=true overrides a site default that hides comments', async () => {
    seedTitledRecord({ show_comments: true })
    renderDetailPage('article', 1, { recordPageConfig: { comments: false, related: true } })

    await screen.findByRole('heading', { level: 1, name: 'My article' })
    expect(screen.getByRole('heading', { name: 'Comments' })).toBeInTheDocument()
  })

  it('show_comments=false hides comments even when the site default shows them', async () => {
    seedTitledRecord({ show_comments: false })
    renderDetailPage()

    await screen.findByRole('heading', { level: 1, name: 'My article' })
    expect(screen.queryByRole('heading', { name: 'Comments' })).not.toBeInTheDocument()
  })

  it('show_related=false hides the "Keep reading" block', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    // A second published record of the same type would otherwise populate the block.
    seedEntities([
      {
        id: 1,
        entity_type_id: 1,
        is_deleted: false,
        deleted_at: null,
        show_related: false,
      },
      {
        id: 2,
        entity_type_id: 1,
        status: 'published',
        published_at: '2026-07-01T00:00:00+00:00',
        is_deleted: false,
        deleted_at: null,
      },
    ])
    seedFieldDefs([{ id: 1, entity_type_id: 1, field_key: 'title', data_type: 'text' }])
    seedTextFields([{ id: 1, entity_id: 1, field_key: 'title', value: 'My article' }])
    renderDetailPage()

    await screen.findByRole('heading', { level: 1, name: 'My article' })
    expect(screen.queryByText('Keep reading')).not.toBeInTheDocument()
  })

  it('renders relation fields as links to target records', async () => {
    seedEntityTypes([
      { id: 1, name: 'Article', slug: 'article' },
      { id: 2, name: 'Author', slug: 'author' },
    ])
    seedEntities([
      { id: 1, entity_type_id: 1, is_deleted: false, deleted_at: null },
      { id: 2, entity_type_id: 2, is_deleted: false, deleted_at: null },
    ])
    seedFieldDefs([
      { id: 1, entity_type_id: 1, field_key: 'title', data_type: 'text' },
      {
        id: 2,
        entity_type_id: 1,
        field_key: 'author',
        data_type: 'relation',
        target_entity_type_id: 2,
        cardinality: 'one',
      },
    ])
    seedTextFields([
      { id: 1, entity_id: 1, field_key: 'title', value: 'My article' },
      { id: 2, entity_id: 2, field_key: 'title', value: 'Alice' },
    ])
    seedEntityRelations([{ source_entity_id: 1, target_entity_id: 2, field_key: 'author' }])

    renderDetailPage('article', 1)

    expect(await screen.findByText('My article')).toBeInTheDocument()
    expect(await screen.findByText('author')).toBeInTheDocument()

    const authorLink = screen.getByRole('link', { name: 'Alice' })
    expect(authorLink).toHaveAttribute('href', '/author/2')
  })

  it('renders body text fields as markdown', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    seedEntities([
      {
        id: 1,
        entity_type_id: 1,
        is_deleted: false,
        deleted_at: null,
      },
    ])
    seedFieldDefs([
      { id: 1, entity_type_id: 1, field_key: 'title', data_type: 'text' },
      { id: 2, entity_type_id: 1, field_key: 'body', data_type: 'text' },
    ])
    seedTextFields([
      {
        id: 1,
        entity_id: 1,
        field_key: 'title',
        value: 'My article',
      },
      {
        id: 2,
        entity_id: 1,
        field_key: 'body',
        value: '## Intro\n\n**bold** paragraph',
      },
    ])

    renderDetailPage()

    expect(await screen.findByRole('heading', { level: 2, name: 'Intro' })).toBeInTheDocument()
    expect(screen.getByText('bold')).toBeInTheDocument()
  })

  it('re-homes an SPA navigation to the pinned front-page permalink (#701)', async () => {
    // The pinned record's canonical home is `/`: reaching its own permalink via an
    // in-app <Link> must replace the URL with the root, mirroring the server-side 302.
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    seedEntities([{ id: 1, entity_type_id: 1, is_deleted: false, deleted_at: null }])
    seedFieldDefs([{ id: 1, entity_type_id: 1, field_key: 'title', data_type: 'text' }])
    seedTextFields([{ id: 1, entity_id: 1, field_key: 'title', value: 'Pinned home' }])

    renderWithProviders(
      <MemoryRouter initialEntries={['/article/1']}>
        <Routes>
          <Route element={<PublicSiteTestProvider site={{ frontPagePath: '/article/1' }} />}>
            <Route path="/" element={<div>home route rendered</div>} />
            <Route path="/:entityTypeSlug/*" element={<PublicRecordDetailPage />} />
          </Route>
        </Routes>
      </MemoryRouter>,
    )

    expect(await screen.findByText('home route rendered')).toBeInTheDocument()
  })

  it('shows not found for a path that is not a known type or a custom permalink', async () => {
    renderDetailPage('unknown', 1)

    expect(await screen.findByText('Record not found')).toBeInTheDocument()
  })

  it('front-page fallback: renders an id-pattern URL the resolver does not know (#748)', async () => {
    // front_page が返す正規パスはパターン由来 URL（例 /article/1）でもよいが、
    // resolve API はカスタム permalink 専用で found:false（MSW 既定）を返す。
    // その場合でもパターン解釈で本文を描画し、notFound にしないこと。
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    seedEntities([{ id: 1, entity_type_id: 1, is_deleted: false, deleted_at: null }])
    seedFieldDefs([{ id: 1, entity_type_id: 1, field_key: 'title', data_type: 'text' }])
    seedTextFields([{ id: 1, entity_id: 1, field_key: 'title', value: 'Pinned pattern home' }])

    renderWithProviders(
      <MemoryRouter initialEntries={['/']}>
        <Routes>
          <Route element={<PublicSiteTestProvider site={{ frontPagePath: '/article/1' }} />}>
            <Route path="/" element={<PublicRecordByPermalink path="/article/1" isFrontPage />} />
          </Route>
        </Routes>
      </MemoryRouter>,
    )

    expect(
      await screen.findByRole('heading', { level: 1, name: 'Pinned pattern home' }),
    ).toBeInTheDocument()
    expect(screen.queryByText('Record not found')).not.toBeInTheDocument()
  })

  it('front-page fallback: renders a slug-pattern URL the resolver does not know (#748)', async () => {
    seedEntityTypes([{ id: 1, name: 'Work', slug: 'work', permalink_pattern: '/{type}/{slug}' }])
    seedEntities([
      {
        id: 7,
        entity_type_id: 1,
        slug: 'company',
        status: 'published',
        is_deleted: false,
        deleted_at: null,
      },
    ])
    seedFieldDefs([{ id: 1, entity_type_id: 1, field_key: 'title', data_type: 'text' }])
    seedTextFields([{ id: 1, entity_id: 7, field_key: 'title', value: 'Slug pattern home' }])

    renderWithProviders(
      <MemoryRouter initialEntries={['/']}>
        <Routes>
          <Route element={<PublicSiteTestProvider site={{ frontPagePath: '/work/company' }} />}>
            <Route
              path="/"
              element={<PublicRecordByPermalink path="/work/company" isFrontPage />}
            />
          </Route>
        </Routes>
      </MemoryRouter>,
    )

    expect(
      await screen.findByRole('heading', { level: 1, name: 'Slug pattern home' }),
    ).toBeInTheDocument()
  })

  it('renders the derived chapter nav and hides series/chapter_no/chapter_total', async () => {
    seedEntityTypes([{ id: 1, name: 'Work', slug: 'work', permalink_pattern: '/{type}/{slug}' }])
    seedEntities([
      {
        id: 5,
        entity_type_id: 1,
        slug: 'bocchan-2',
        status: 'published',
        is_deleted: false,
        deleted_at: null,
      },
    ])
    seedFieldDefs([
      { id: 1, entity_type_id: 1, field_key: 'title', data_type: 'text' },
      { id: 2, entity_type_id: 1, field_key: 'body', data_type: 'text' },
      { id: 3, entity_type_id: 1, field_key: 'series', data_type: 'text' },
      { id: 4, entity_type_id: 1, field_key: 'chapter_no', data_type: 'int' },
      { id: 5, entity_type_id: 1, field_key: 'chapter_total', data_type: 'int' },
    ])
    seedTextFields([
      { id: 1, entity_id: 5, field_key: 'title', value: 'Bocchan ch.2' },
      { id: 2, entity_id: 5, field_key: 'body', value: 'Chapter body text.' },
      { id: 3, entity_id: 5, field_key: 'series', value: 'bocchan' },
    ])
    seedIntFields([
      { id: 1, entity_id: 5, field_key: 'chapter_no', value: 2 },
      { id: 2, entity_id: 5, field_key: 'chapter_total', value: 3 },
    ])

    renderWithProviders(
      <MemoryRouter initialEntries={['/work/bocchan-2']}>
        <Routes>
          <Route element={<PublicSiteTestProvider />}>
            <Route path="/:entityTypeSlug/*" element={<PublicRecordDetailPage />} />
          </Route>
        </Routes>
      </MemoryRouter>,
    )

    // The chapter title is lifted into the masthead h1.
    expect(
      await screen.findByRole('heading', { level: 1, name: 'Bocchan ch.2' }),
    ).toBeInTheDocument()

    // Derived nav (rendered at the top and bottom of the chapter), with sibling
    // URLs resolved from the work's slug permalink — no extra fetch.
    const contentsLinks = screen.getAllByRole('link', { name: 'Contents' })
    expect(contentsLinks.length).toBeGreaterThanOrEqual(1)
    contentsLinks.forEach((link) => {
      expect(link).toHaveAttribute('href', '/work/bocchan')
    })
    screen.getAllByRole('link', { name: /Previous chapter/ }).forEach((link) => {
      expect(link).toHaveAttribute('href', '/work/bocchan-1')
    })
    screen.getAllByRole('link', { name: /Next chapter/ }).forEach((link) => {
      expect(link).toHaveAttribute('href', '/work/bocchan-3')
    })
    expect(screen.getAllByText('Chapter 2 / 3').length).toBeGreaterThanOrEqual(1)

    // The reserved chapter-nav metadata is never shown as an ordinary field row.
    expect(screen.queryByText('series')).toBeNull()
    expect(screen.queryByText('chapter_no')).toBeNull()
    expect(screen.queryByText('chapter_total')).toBeNull()
  })
})
