/**
 * Category: Auth Lifecycle
 *
 * Tests the full authentication lifecycle: token storage, persistence across
 * page navigations, Authorization header propagation, session expiry, role
 * switching, and 401-triggered redirect flows.
 *
 * Adapted from nene-corpus 13-session-lifecycle.spec.ts patterns — translated
 * from "chat session token forwarding" to "admin JWT lifecycle".
 *
 * Patterns covered:
 *  - Authorization Bearer header forwarded on every API request
 *  - Token persists in localStorage across multiple page navigations
 *  - localStorage cleared → RequireAuth redirects to /login
 *  - Session data (token + role) stored before first API call
 *  - Correct role stored in localStorage after bypassLogin
 *  - Expired token → RequireAuth redirects to /login
 *  - Role switch (admin → editor) → reduced capabilities reflected immediately
 *  - 401 API response → client clears session + window.location.href = /login
 *  - Multiple pages visited sequentially — all API calls carry Authorization
 *  - POST request also carries Authorization Bearer (not just GETs)
 */

import { test, expect } from '@playwright/test';
import {
  bypassLogin,
  gotoAdmin,
  gotoSuperadmin,
  mockEntityTypesEndpoint,
} from '../fixtures/helpers.js';
import {
  ADMIN_TOKEN,
  ENTITY_TYPE_LIST_EMPTY,
  ENTITY_TYPE_CREATED,
  TAG_LIST_EMPTY,
  USER_LIST_EMPTY,
} from '../fixtures/api-mocks.js';

/** localStorage key used by authStore (mirrors helpers.ts AUTH_STORAGE_KEY) */
const AUTH_KEY = 'nene_records_token';

/** Injects a session directly into localStorage, bypassing bypassLogin helper. */
async function injectSession(
  page: import('@playwright/test').Page,
  session: {
    token: string;
    expiresAt: string;
    email: string;
    role: string;
  },
): Promise<void> {
  await page.evaluate(
    ([key, value]) => {
      localStorage.setItem(key, value);
    },
    [AUTH_KEY, JSON.stringify(session)] as [string, string],
  );
}

test.describe('Auth Lifecycle', () => {
  // ── 16-01: Authorization Bearer forwarded on every entity-types GET ────────

  test('16-01: Authorization Bearer header present on every entity-types GET after bypassLogin', async ({ page }) => {
    await bypassLogin(page);

    const capturedAuthHeaders: string[] = [];

    // Register AFTER bypassLogin so this handler wins (LIFO priority)
    await page.route('**/api/v1/entity-types**', (route) => {
      const auth = route.request().headers()['authorization'] ?? '';
      capturedAuthHeaders.push(auth);
      return route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify(ENTITY_TYPE_LIST_EMPTY),
      });
    });

    await gotoAdmin(page, '/entity-types');

    // At least the sidebar GET and the page GET were captured
    expect(capturedAuthHeaders.length).toBeGreaterThanOrEqual(1);

    // Every captured request must carry the exact Bearer token
    const expected = `Bearer ${ADMIN_TOKEN}`;
    for (const h of capturedAuthHeaders) {
      expect(h).toBe(expected);
    }
  });

  // ── 16-02: Token persists in localStorage across 3 page navigations ────────

  test('16-02: auth token persists in localStorage after navigating entity-types → tags → users', async ({ page }) => {
    await bypassLogin(page);

    // Stub all three endpoints
    await page.route('**/api/v1/entity-types**', (route) =>
      route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(ENTITY_TYPE_LIST_EMPTY) }),
    );
    await page.route('**/api/v1/tags**', (route) =>
      route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(TAG_LIST_EMPTY) }),
    );
    await page.route('**/api/v1/users**', (route) =>
      route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(USER_LIST_EMPTY) }),
    );

    const readToken = async () =>
      page.evaluate((key) => localStorage.getItem(key), AUTH_KEY);

    // Token present immediately after bypassLogin (before any navigation)
    const tokenInitial = await readToken();
    expect(tokenInitial).not.toBeNull();

    // Navigate to entity-types
    await gotoAdmin(page, '/entity-types');
    const tokenAfterNav1 = await readToken();
    expect(tokenAfterNav1).toBe(tokenInitial);

    // Navigate to tags
    await gotoAdmin(page, '/tags');
    const tokenAfterNav2 = await readToken();
    expect(tokenAfterNav2).toBe(tokenInitial);

    // Navigate to users
    await gotoAdmin(page, '/users');
    const tokenAfterNav3 = await readToken();
    expect(tokenAfterNav3).toBe(tokenInitial);
  });

  // ── 16-03: localStorage cleared → RequireAuth redirects to /login ─────────

  test('16-03: localStorage cleared → navigating to /admin redirects to /login', async ({ page }) => {
    await bypassLogin(page);

    await page.route('**/api/v1/entity-types**', (route) =>
      route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(ENTITY_TYPE_LIST_EMPTY) }),
    );

    // Verify admin page loads while token present
    await gotoAdmin(page, '/entity-types');
    await expect(page.locator('h1')).toBeVisible({ timeout: 6000 });

    // Clear localStorage
    await page.evaluate((key) => {
      localStorage.removeItem(key);
    }, AUTH_KEY);

    // Navigate to admin — RequireAuth fires and redirects to /login
    await page.goto('http://localhost:4173/admin');
    await expect(page).toHaveURL(/\/login/, { timeout: 6000 });
  });

  // ── 16-04: Session data stored before first gotoAdmin API call ─────────────

  test('16-04: token stored in localStorage before first gotoAdmin call', async ({ page }) => {
    // bypassLogin sets localStorage before gotoAdmin navigates
    await bypassLogin(page);

    // Check localStorage BEFORE calling gotoAdmin
    const raw = await page.evaluate((key) => localStorage.getItem(key), AUTH_KEY);
    expect(raw).not.toBeNull();

    const session = JSON.parse(raw!) as { token: string; role: string; email: string };
    expect(session.token).toBe(ADMIN_TOKEN);
    expect(session.email).toBe('admin@example.com');
  });

  // ── 16-05: Correct role stored in localStorage after bypassLogin ───────────

  test('16-05: bypassLogin with editor role → role="editor" stored in localStorage', async ({ page }) => {
    await bypassLogin(page, { role: 'editor', email: 'editor@example.com' });

    const raw = await page.evaluate((key) => localStorage.getItem(key), AUTH_KEY);
    expect(raw).not.toBeNull();

    const session = JSON.parse(raw!) as { token: string; role: string; email: string };
    expect(session.role).toBe('editor');
    expect(session.email).toBe('editor@example.com');
    expect(session.token).toBe(ADMIN_TOKEN);
  });

  // ── 16-06: Expired token → RequireAuth redirects to /login ────────────────

  test('16-06: expired token in localStorage → RequireAuth redirects to /login', async ({ page }) => {
    // Navigate to base origin first so we can set localStorage
    await page.goto('http://localhost:4173');

    // Inject an expired session
    await injectSession(page, {
      token: 'expired-token-xyz',
      expiresAt: '2020-01-01T00:00:00Z', // well in the past
      email: 'admin@example.com',
      role: 'admin',
    });

    // Navigate to admin — authStore.isAuthenticated() returns false (expired) → redirect
    await page.goto('http://localhost:4173/admin');
    await expect(page).toHaveURL(/\/login/, { timeout: 6000 });
  });

  // ── 16-07: Role switch admin → editor — editor sees no create form ─────────

  test('16-07: switch from admin to editor role in localStorage → entity types create form disappears', async ({ page }) => {
    // ── Step 1: admin — create form visible ─────────────────────────────
    await bypassLogin(page, { role: 'admin' });

    await page.route('**/api/v1/entity-types**', (route) =>
      route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(ENTITY_TYPE_LIST_EMPTY) }),
    );

    await gotoAdmin(page, '/entity-types');
    await expect(page.locator('input[id="entity-type-name"]')).toBeVisible({ timeout: 6000 });

    // ── Step 2: overwrite session with editor role ───────────────────────
    await injectSession(page, {
      token: ADMIN_TOKEN,
      expiresAt: '2099-01-01T00:00:00Z',
      email: 'editor@example.com',
      role: 'editor',
    });

    // Re-navigate so the app re-reads localStorage
    await gotoAdmin(page, '/entity-types');

    // Editor does not have manage_schema capability → no create form
    await expect(page.locator('input[id="entity-type-name"]')).not.toBeVisible({ timeout: 6000 });
  });

  // ── 16-08: 401 API response → client clears session → redirect to /login ──

  test('16-08: GET /entity-types returns 401 → authStore cleared → redirected to /login', async ({ page }) => {
    await bypassLogin(page);

    // Register 401 handler AFTER bypassLogin (takes priority)
    await page.route('**/api/v1/entity-types**', (route) =>
      route.fulfill({
        status: 401,
        contentType: 'application/json',
        body: JSON.stringify({ type: 'about:blank', title: 'Unauthorized', status: 401, detail: 'Token expired', instance: '/api/v1/entity-types' }),
      }),
    );

    // Navigate — RequireAuth passes (token valid), then sidebar GET → 401 → window.location.href = '/login'
    await page.goto('http://localhost:4173/admin/entity-types');

    await expect(page).toHaveURL(/\/login/, { timeout: 10000 });
  });

  // ── 16-09: Multiple pages visited — every API request carries Authorization ─

  test('16-09: visiting entity-types, tags, and users sequentially — all requests have Authorization header', async ({ page }) => {
    await bypassLogin(page);

    const capturedHeaders: string[] = [];

    // Register capture handlers AFTER bypassLogin (LIFO: higher priority)
    await page.route('**/api/v1/entity-types**', (route) => {
      capturedHeaders.push(route.request().headers()['authorization'] ?? '');
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(ENTITY_TYPE_LIST_EMPTY) });
    });
    await page.route('**/api/v1/tags**', (route) => {
      capturedHeaders.push(route.request().headers()['authorization'] ?? '');
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(TAG_LIST_EMPTY) });
    });
    await page.route('**/api/v1/users**', (route) => {
      capturedHeaders.push(route.request().headers()['authorization'] ?? '');
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(USER_LIST_EMPTY) });
    });

    await gotoAdmin(page, '/entity-types');
    await gotoAdmin(page, '/tags');
    await gotoAdmin(page, '/users');

    // Captured at least 3 headers (one per page-specific endpoint)
    expect(capturedHeaders.length).toBeGreaterThanOrEqual(3);

    // Every captured header must be the exact Bearer token
    const expected = `Bearer ${ADMIN_TOKEN}`;
    for (const h of capturedHeaders) {
      expect(h).toBe(expected);
    }
  });

  // ── 16-10: POST request also carries Authorization Bearer ──────────────────

  test('16-10: POST /entity-types carries Authorization Bearer token (not just GETs)', async ({ page }) => {
    await bypassLogin(page);

    let postAuthHeader: string | undefined;
    let getAuthHeader: string | undefined;

    await page.route('**/api/v1/entity-types**', (route) => {
      const method = route.request().method();
      const auth = route.request().headers()['authorization'] ?? '';

      if (method === 'POST') {
        postAuthHeader = auth;
        return route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify(ENTITY_TYPE_CREATED),
        });
      }
      if (method === 'GET') {
        getAuthHeader = auth;
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(ENTITY_TYPE_LIST_EMPTY),
        });
      }
      return route.continue();
    });

    await gotoAdmin(page, '/entity-types');

    // Fill and submit the create form
    await page.locator('input[id="entity-type-name"]').fill('TestType');
    await page.locator('input[id="entity-type-slug"]').fill('testtype');
    await page.locator('button:has-text("Create content type")').click();

    // Wait for the POST to complete (form resets)
    await expect(page.locator('input[id="entity-type-name"]')).toHaveValue('', { timeout: 6000 });

    const expected = `Bearer ${ADMIN_TOKEN}`;
    expect(getAuthHeader).toBe(expected);
    expect(postAuthHeader).toBe(expected);
  });
});
