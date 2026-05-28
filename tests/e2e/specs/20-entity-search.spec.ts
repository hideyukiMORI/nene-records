/**
 * Category: Entity Records — Full-text search form (#265)
 *
 * Verifies the Admin SPA entity records search box:
 *  - filters the list via the `q` query param
 *  - reflects the query in the URL (?q=) so searches are bookmarkable
 *  - initializes the search box from a `?q=` URL on load
 *
 * All API calls are intercepted — no real backend required.
 * Routes registered AFTER bypassLogin take LIFO priority over the
 * default entity-types handler registered inside bypassLogin.
 */

import { test, expect } from '@playwright/test';
import { bypassLogin, gotoAdmin } from '../fixtures/helpers.js';

function makeEntityType(id: number, name: string, slug: string) {
  return {
    id,
    name,
    slug,
    is_pinned: false,
    labels: null,
    permalink_pattern: null,
    previous_permalink_pattern: null,
  };
}

function makeEntity(id: number, entityTypeId: number, slug: string) {
  return {
    id,
    entity_type_id: entityTypeId,
    slug,
    status: 'draft' as const,
    published_at: null,
    scheduled_at: null,
    is_deleted: false,
    deleted_at: null,
    meta_title: null,
    meta_description: null,
    created_at: '2026-06-01T00:00:00Z',
    updated_at: '2026-06-01T00:00:00Z',
  };
}

const EMPTY_LIST = JSON.stringify({ items: [], limit: 20, offset: 0, total: 0 });

/**
 * Register all routes needed by the entity records page. The entities handler
 * honors the `q` param (slug substring, case-insensitive) so the search box
 * actually filters.
 */
async function setupRecordsPage(page: import('@playwright/test').Page) {
  const entities = [makeEntity(1, 1, 'alpha-post'), makeEntity(2, 1, 'beta-post')];

  await page.route('**/api/v1/entity-types**', (route) => {
    if (route.request().method() === 'GET') {
      return route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          items: [makeEntityType(1, 'Articles', 'articles')],
          limit: 100,
          offset: 0,
        }),
      });
    }
    return route.continue();
  });

  await page.route('**/api/v1/field-defs**', (route) =>
    route.request().method() === 'GET'
      ? route.fulfill({ status: 200, contentType: 'application/json', body: EMPTY_LIST })
      : route.continue(),
  );
  await page.route('**/api/v1/text-fields**', (route) =>
    route.request().method() === 'GET'
      ? route.fulfill({ status: 200, contentType: 'application/json', body: EMPTY_LIST })
      : route.continue(),
  );
  await page.route('**/api/v1/tags**', (route) =>
    route.request().method() === 'GET'
      ? route.fulfill({ status: 200, contentType: 'application/json', body: EMPTY_LIST })
      : route.continue(),
  );

  await page.route('**/api/v1/entities**', (route) => {
    if (route.request().method() !== 'GET') {
      return route.continue();
    }
    const url = new URL(route.request().url());
    const q = url.searchParams.get('q');
    const filtered =
      q !== null && q.trim() !== ''
        ? entities.filter((e) => e.slug.toLowerCase().includes(q.trim().toLowerCase()))
        : entities;
    return route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ items: filtered, limit: 20, offset: 0, total: filtered.length }),
    });
  });
}

test.describe('Entity Records — search', () => {
  test('20-01: search box filters records and reflects the query in the URL', async ({ page }) => {
    await bypassLogin(page);
    await setupRecordsPage(page);

    await gotoAdmin(page, '/articles');
    await expect(page.locator('h1')).toContainText('Articles', { timeout: 6000 });
    await expect(page.locator('text=Item #1')).toBeVisible({ timeout: 6000 });
    await expect(page.locator('text=Item #2')).toBeVisible();

    await page.getByLabel('Search…').fill('alpha');

    await expect(page).toHaveURL(/[?&]q=alpha/, { timeout: 6000 });
    await expect(page.locator('text=Item #1')).toBeVisible();
    await expect(page.locator('text=Item #2')).toHaveCount(0);
  });

  test('20-02: ?q= in the URL initializes the search box and filters on load', async ({ page }) => {
    await bypassLogin(page);
    await setupRecordsPage(page);

    await gotoAdmin(page, '/articles?q=beta');

    await expect(page.getByLabel('Search…')).toHaveValue('beta', { timeout: 6000 });
    await expect(page.locator('text=Item #2')).toBeVisible();
    await expect(page.locator('text=Item #1')).toHaveCount(0);
  });

  test('20-03: clearing the search restores all records and drops ?q=', async ({ page }) => {
    await bypassLogin(page);
    await setupRecordsPage(page);

    await gotoAdmin(page, '/articles?q=alpha');
    await expect(page.locator('text=Item #1')).toBeVisible({ timeout: 6000 });
    await expect(page.locator('text=Item #2')).toHaveCount(0);

    await page.getByLabel('Clear search').click();

    await expect(page).not.toHaveURL(/[?&]q=/, { timeout: 6000 });
    await expect(page.locator('text=Item #1')).toBeVisible();
    await expect(page.locator('text=Item #2')).toBeVisible();
  });
});
