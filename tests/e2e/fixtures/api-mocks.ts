/**
 * Default mock response shapes for the NeNe Records Admin SPA API endpoints.
 *
 * All constants here are pure data — no page.route() calls.
 * Import them in spec files and pass to the helper functions in helpers.ts.
 *
 * IMPORTANT: shapes must match the exact DTO format that the frontend mapper expects.
 */

// ── Auth ──────────────────────────────────────────────────────────────────────

export const ADMIN_TOKEN = 'test-admin-jwt-token-abcdef';

export const DEFAULT_LOGIN_RESPONSE = {
  token: ADMIN_TOKEN,
  expires_at: '2099-01-01T00:00:00Z',
  email: 'admin@example.com',
  role: 'admin',
};

export const SUPERADMIN_LOGIN_RESPONSE = {
  token: ADMIN_TOKEN,
  expires_at: '2099-01-01T00:00:00Z',
  email: 'superadmin@example.com',
  role: 'superadmin',
};

// ── Dashboard ─────────────────────────────────────────────────────────────────
// Shape: DashboardSummaryDto { recent_published, today_access_count, this_month_access_count, entity_type_summary }

export const DASHBOARD_EMPTY = {
  recent_published: [],
  today_access_count: 0,
  this_month_access_count: 0,
  entity_type_summary: [],
};

export const DASHBOARD_WITH_CONTENT = {
  recent_published: [],
  today_access_count: 10,
  this_month_access_count: 50,
  entity_type_summary: [
    {
      entity_type_id: 1,
      entity_type_name: 'Posts',
      entity_type_slug: 'posts',
      published_count: 5,
      draft_count: 2,
    },
    {
      entity_type_id: 2,
      entity_type_name: 'Pages',
      entity_type_slug: 'pages',
      published_count: 3,
      draft_count: 0,
    },
  ],
};

// ── Entity Types ──────────────────────────────────────────────────────────────
// Shape: EntityTypeListDto { items: EntityTypeDto[], limit, offset }

export const ENTITY_TYPE_LIST_EMPTY = {
  items: [],
  limit: 20,
  offset: 0,
};

export const ENTITY_TYPE_LIST = {
  items: [
    {
      id: 1,
      name: 'Posts',
      slug: 'posts',
      is_pinned: true,
      labels: null,
      permalink_pattern: null,
      previous_permalink_pattern: null,
    },
    {
      id: 2,
      name: 'Pages',
      slug: 'pages',
      is_pinned: false,
      labels: null,
      permalink_pattern: null,
      previous_permalink_pattern: null,
    },
  ],
  limit: 20,
  offset: 0,
};

export const ENTITY_TYPE_CREATED = {
  id: 3,
  name: 'Events',
  slug: 'events',
  is_pinned: false,
  labels: null,
  permalink_pattern: null,
  previous_permalink_pattern: null,
};

// ── Tags ──────────────────────────────────────────────────────────────────────
// Shape: TagListDto { items: TagDto[], limit, offset }

export const TAG_LIST_EMPTY = {
  items: [],
  limit: 20,
  offset: 0,
};

export const TAG_LIST = {
  items: [
    { id: 1, slug: 'technology', name: 'Technology' },
    { id: 2, slug: 'design', name: 'Design' },
    { id: 3, slug: 'news', name: 'News' },
  ],
  limit: 20,
  offset: 0,
};

export const TAG_CREATED = {
  id: 4,
  slug: 'tutorial',
  name: 'Tutorial',
};

// ── Users ─────────────────────────────────────────────────────────────────────
// Shape: UserListDto { users: UserDto[] }

export const USER_LIST_EMPTY = {
  users: [],
};

export const USER_LIST = {
  users: [
    {
      id: 1,
      email: 'admin@example.com',
      role: 'admin',
      status: 'active',
      created_at: '2024-01-01T00:00:00Z',
      updated_at: '2024-01-01T00:00:00Z',
    },
    {
      id: 2,
      email: 'editor@example.com',
      role: 'editor',
      status: 'active',
      created_at: '2024-01-02T00:00:00Z',
      updated_at: '2024-01-02T00:00:00Z',
    },
  ],
};

// ── Organizations (Superadmin) ─────────────────────────────────────────────────
// Shape: OrganizationListDto { data: OrganizationDto[], meta: { total, limit, offset } }

export const ORGANIZATION_LIST_EMPTY = {
  data: [],
  meta: { total: 0, limit: 50, offset: 0 },
};

export const ORGANIZATION_LIST = {
  data: [
    {
      id: 1,
      name: 'Acme Corp',
      slug: 'acme',
      plan: 'pro',
      is_active: true,
      custom_domain: null,
      created_at: '2026-01-01T00:00:00Z',
      updated_at: '2026-01-01T00:00:00Z',
    },
    {
      id: 2,
      name: 'Globex Inc',
      slug: 'globex',
      plan: 'starter',
      is_active: true,
      custom_domain: null,
      created_at: '2026-02-01T00:00:00Z',
      updated_at: '2026-02-01T00:00:00Z',
    },
  ],
  meta: { total: 2, limit: 50, offset: 0 },
};

export const ORGANIZATION_DETAIL = {
  id: 1,
  name: 'Acme Corp',
  slug: 'acme',
  plan: 'pro',
  is_active: true,
  custom_domain: null,
  created_at: '2026-01-01T00:00:00Z',
  updated_at: '2026-01-01T00:00:00Z',
};
