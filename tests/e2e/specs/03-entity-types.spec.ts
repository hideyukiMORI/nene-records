/**
 * Category: Entity Types Management
 *
 * Tests creating, listing, and managing content types.
 */

import { test, expect } from '@playwright/test';
import {
  bypassLogin,
  gotoAdmin,
  mockEntityTypesEndpoint,
  mockDashboard,
} from '../fixtures/helpers.js';
import {
  ENTITY_TYPE_LIST_EMPTY,
  ENTITY_TYPE_LIST,
  ENTITY_TYPE_CREATED,
  DASHBOARD_EMPTY,
} from '../fixtures/api-mocks.js';

test.describe('Entity Types', () => {
  test('03-01: page title — "Content types"', async ({ page }) => {
    await bypassLogin(page);
    await mockEntityTypesEndpoint(page, ENTITY_TYPE_LIST_EMPTY);
    await gotoAdmin(page, '/entity-types');

    await expect(page.locator('h1')).toContainText('Content types');
  });

  test('03-02: empty state — shows "No content types yet"', async ({ page }) => {
    await bypassLogin(page);
    await mockEntityTypesEndpoint(page, ENTITY_TYPE_LIST_EMPTY);
    await gotoAdmin(page, '/entity-types');

    await expect(page.locator('text=No content types yet')).toBeVisible({ timeout: 6000 });
  });

  test('03-03: existing list — entity type names are shown', async ({ page }) => {
    await bypassLogin(page);
    await mockEntityTypesEndpoint(page, ENTITY_TYPE_LIST);
    await gotoAdmin(page, '/entity-types');

    await expect(page.locator('text=Posts').first()).toBeVisible({ timeout: 6000 });
    await expect(page.locator('text=Pages').first()).toBeVisible({ timeout: 6000 });
  });

  test('03-04: create form — name and slug inputs are present', async ({ page }) => {
    await bypassLogin(page);
    await mockEntityTypesEndpoint(page, ENTITY_TYPE_LIST_EMPTY);
    await gotoAdmin(page, '/entity-types');

    // Create form should be visible on the page
    await expect(page.locator('input[id="entity-type-name"]')).toBeVisible({ timeout: 6000 });
    await expect(page.locator('input[id="entity-type-slug"]')).toBeVisible({ timeout: 6000 });
  });

  test('03-05: create entity type — calls POST and shows new type', async ({ page }) => {
    await bypassLogin(page);

    // Single handler: POST fulfils with created item; GET toggles to show it after creation
    let created = false;
    await page.route('**/api/v1/entity-types**', (route) => {
      if (route.request().method() === 'POST') {
        created = true;
        return route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify(ENTITY_TYPE_CREATED),
        });
      }
      if (route.request().method() === 'GET') {
        const list = created
          ? { items: [ENTITY_TYPE_CREATED], limit: 20, offset: 0 }
          : ENTITY_TYPE_LIST_EMPTY;
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(list),
        });
      }
      return route.continue();
    });

    await gotoAdmin(page, '/entity-types');

    await page.locator('input[id="entity-type-name"]').fill('Events');
    await page.locator('input[id="entity-type-slug"]').fill('events');
    await page.locator('button:has-text("Create content type")').click();

    await expect(page.locator('text=Events').first()).toBeVisible({ timeout: 6000 });
  });

  test('03-06: load error — shows error message', async ({ page }) => {
    await bypassLogin(page);
    await page.route('**/api/v1/entity-types**', (route) =>
      route.fulfill({ status: 500, contentType: 'application/json', body: '{"detail":"Error"}' }),
    );
    await gotoAdmin(page, '/entity-types');

    await expect(page.locator('text=Could not load content types')).toBeVisible({ timeout: 6000 });
  });

  test('03-07: editor role — create form not shown', async ({ page }) => {
    await bypassLogin(page, { role: 'editor' });
    await mockEntityTypesEndpoint(page, ENTITY_TYPE_LIST_EMPTY);
    await gotoAdmin(page, '/entity-types');

    // Editor should be redirected to /forbidden (no manage_schema capability)
    await expect(page).toHaveURL(/\/(forbidden|login)/, { timeout: 6000 });
  });
});
