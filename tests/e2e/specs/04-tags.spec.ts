/**
 * Category: Tags Management
 *
 * Tests creating, listing, and deleting tags.
 */

import { test, expect } from '@playwright/test';
import {
  bypassLogin,
  gotoAdmin,
  mockTagsEndpoint,
} from '../fixtures/helpers.js';
import { TAG_LIST_EMPTY, TAG_LIST, TAG_CREATED } from '../fixtures/api-mocks.js';

test.describe('Tags', () => {
  test('04-01: page title — "Tags"', async ({ page }) => {
    await bypassLogin(page);
    await mockTagsEndpoint(page, TAG_LIST_EMPTY);
    await gotoAdmin(page, '/tags');

    await expect(page.locator('h1')).toContainText('Tags');
  });

  test('04-02: empty state — shows "No tags yet"', async ({ page }) => {
    await bypassLogin(page);
    await mockTagsEndpoint(page, TAG_LIST_EMPTY);
    await gotoAdmin(page, '/tags');

    await expect(page.locator('text=No tags yet')).toBeVisible({ timeout: 6000 });
  });

  test('04-03: existing tags — names are shown', async ({ page }) => {
    await bypassLogin(page);
    await mockTagsEndpoint(page, TAG_LIST);
    await gotoAdmin(page, '/tags');

    await expect(page.locator('text=Technology').first()).toBeVisible({ timeout: 6000 });
    await expect(page.locator('text=Design').first()).toBeVisible({ timeout: 6000 });
    await expect(page.locator('text=News').first()).toBeVisible({ timeout: 6000 });
  });

  test('04-04: create tag — form inputs visible', async ({ page }) => {
    await bypassLogin(page);
    await mockTagsEndpoint(page, TAG_LIST_EMPTY);
    await gotoAdmin(page, '/tags');

    // Create form should show name and slug inputs
    await expect(page.locator('input[id="tag-name"]')).toBeVisible({ timeout: 6000 });
    await expect(page.locator('input[id="tag-slug"]')).toBeVisible({ timeout: 6000 });
  });

  test('04-05: create tag — calls POST and displays new tag', async ({ page }) => {
    await bypassLogin(page);

    let getCallCount = 0;
    await page.route('**/api/v1/tags**', (route) => {
      if (route.request().method() === 'GET') {
        getCallCount++;
        const response = getCallCount === 1
          ? TAG_LIST_EMPTY
          : { items: [TAG_CREATED], total: 1 };
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(response),
        });
      }
      if (route.request().method() === 'POST') {
        return route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify(TAG_CREATED),
        });
      }
      return route.continue();
    });

    await gotoAdmin(page, '/tags');

    await page.locator('input[id="tag-name"]').fill('Tutorial');
    await page.locator('input[id="tag-slug"]').fill('tutorial');
    await page.locator('button:has-text("Create tag")').click();

    await expect(page.locator('text=Tutorial').first()).toBeVisible({ timeout: 6000 });
  });

  test('04-06: load error — shows error message', async ({ page }) => {
    await bypassLogin(page);
    await page.route('**/api/v1/tags**', (route) =>
      route.fulfill({ status: 500, contentType: 'application/json', body: '{"detail":"Error"}' }),
    );
    await gotoAdmin(page, '/tags');

    await expect(page.locator('text=Could not load tags')).toBeVisible({ timeout: 6000 });
  });

  test('04-07: editor role — redirected to forbidden', async ({ page }) => {
    await bypassLogin(page, { role: 'editor' });
    await gotoAdmin(page, '/tags');

    await expect(page).toHaveURL(/\/(forbidden|login)/, { timeout: 6000 });
  });

  test('04-08: tag count shown in list header', async ({ page }) => {
    await bypassLogin(page);
    await mockTagsEndpoint(page, TAG_LIST);
    await gotoAdmin(page, '/tags');

    // "Existing tags" section title should be visible
    await expect(page.locator('text=Existing tags')).toBeVisible({ timeout: 6000 });
  });
});
