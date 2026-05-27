/**
 * Category: UI States
 *
 * Tests loading indicators, disabled states during API calls,
 * double-submit prevention, and optimistic state handling.
 *
 * Patterns inspired by nene-corpus 07-ui-states.spec.ts.
 */

import { test, expect } from '@playwright/test';
import {
  bypassLogin,
  gotoAdmin,
  mockEntityTypesEndpoint,
  mockTagsEndpoint,
} from '../fixtures/helpers.js';
import {
  ENTITY_TYPE_LIST_EMPTY,
  TAG_LIST_EMPTY,
  ENTITY_TYPE_CREATED,
  TAG_CREATED,
} from '../fixtures/api-mocks.js';

test.describe('UI States', () => {
  // ── 09-01: Submit button shows loading text during entity type creation ─────

  test('09-01: entity type create button shows "Creating…" while POST is in-flight', async ({ page }) => {
    await bypassLogin(page);

    // Delayed POST so we can observe the in-flight state
    await page.route('**/api/v1/entity-types**', async (route) => {
      if (route.request().method() === 'POST') {
        await new Promise((r) => setTimeout(r, 600));
        return route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify(ENTITY_TYPE_CREATED),
        });
      }
      if (route.request().method() === 'GET') {
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(ENTITY_TYPE_LIST_EMPTY),
        });
      }
      return route.continue();
    });

    await gotoAdmin(page, '/entity-types');

    await page.locator('input[id="entity-type-name"]').fill('Events');
    await page.locator('input[id="entity-type-slug"]').fill('events');
    await page.locator('button:has-text("Create content type")').click();

    // While POST is in-flight, button text should change to submitting state
    await expect(page.locator('button:has-text("Creating…")')).toBeVisible({ timeout: 3000 });

    // After response, button returns to normal text
    await expect(page.locator('button:has-text("Create content type")')).toBeVisible({ timeout: 6000 });
  });

  // ── 09-02: Form inputs disabled during entity type creation ────────────────

  test('09-02: entity type name/slug inputs disabled while POST is in-flight', async ({ page }) => {
    await bypassLogin(page);

    await page.route('**/api/v1/entity-types**', async (route) => {
      if (route.request().method() === 'POST') {
        await new Promise((r) => setTimeout(r, 600));
        return route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify(ENTITY_TYPE_CREATED),
        });
      }
      if (route.request().method() === 'GET') {
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(ENTITY_TYPE_LIST_EMPTY),
        });
      }
      return route.continue();
    });

    await gotoAdmin(page, '/entity-types');

    const nameInput = page.locator('input[id="entity-type-name"]');
    const slugInput = page.locator('input[id="entity-type-slug"]');

    await nameInput.fill('Events');
    await slugInput.fill('events');
    await page.locator('button:has-text("Create content type")').click();

    // Inputs should be disabled while POST is in-flight
    await expect(nameInput).toBeDisabled({ timeout: 2000 });
    await expect(slugInput).toBeDisabled({ timeout: 2000 });

    // After response, inputs re-enabled
    await expect(nameInput).toBeEnabled({ timeout: 6000 });
    await expect(slugInput).toBeEnabled({ timeout: 6000 });
  });

  // ── 09-03: Double-submit prevented for entity type creation ───────────────

  test('09-03: double-click on entity type create button results in exactly one POST', async ({ page }) => {
    await bypassLogin(page);

    let postCallCount = 0;
    await page.route('**/api/v1/entity-types**', async (route) => {
      if (route.request().method() === 'POST') {
        postCallCount++;
        await new Promise((r) => setTimeout(r, 400));
        return route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify(ENTITY_TYPE_CREATED),
        });
      }
      if (route.request().method() === 'GET') {
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(ENTITY_TYPE_LIST_EMPTY),
        });
      }
      return route.continue();
    });

    await gotoAdmin(page, '/entity-types');

    await page.locator('input[id="entity-type-name"]').fill('Events');
    await page.locator('input[id="entity-type-slug"]').fill('events');

    const submitBtn = page.locator('button:has-text("Create content type")');
    await submitBtn.click();
    // Rapid second and third clicks while disabled
    await submitBtn.click({ force: true });
    await submitBtn.click({ force: true });

    // Wait for response to complete
    await expect(page.locator('button:has-text("Create content type")')).toBeVisible({ timeout: 6000 });

    // Should be exactly one POST despite multiple clicks
    expect(postCallCount).toBe(1);
  });

  // ── 09-04: Tag create button shows loading state ───────────────────────────

  test('09-04: tag create button shows "Creating…" while POST is in-flight', async ({ page }) => {
    await bypassLogin(page);

    await page.route('**/api/v1/tags**', async (route) => {
      if (route.request().method() === 'POST') {
        await new Promise((r) => setTimeout(r, 600));
        return route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify(TAG_CREATED),
        });
      }
      if (route.request().method() === 'GET') {
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(TAG_LIST_EMPTY),
        });
      }
      return route.continue();
    });

    await gotoAdmin(page, '/tags');

    await page.locator('input[id="tag-name"]').fill('Tutorial');
    await page.locator('input[id="tag-slug"]').fill('tutorial');
    await page.locator('button:has-text("Create tag")').click();

    // In-flight state
    await expect(page.locator('button:has-text("Creating…")')).toBeVisible({ timeout: 3000 });

    // After response
    await expect(page.locator('button:has-text("Create tag")')).toBeVisible({ timeout: 6000 });
  });

  // ── 09-05: Error message does not stack — only one error instance ──────────

  test('09-05: entity types load error — single error message shown, not duplicated', async ({ page }) => {
    await bypassLogin(page);

    // All GETs always fail — lets us check that errors don't stack on retry
    await page.route('**/api/v1/entity-types**', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({
          status: 500,
          contentType: 'application/json',
          body: '{"detail":"Server error"}',
        });
      }
      return route.continue();
    });

    await gotoAdmin(page, '/entity-types');

    // Error shown exactly once (not duplicated in DOM)
    await expect(page.locator('text=Could not load content types')).toBeVisible({ timeout: 6000 });
    const errorCount = await page.locator('text=Could not load content types').count();
    expect(errorCount).toBe(1);

    // Retry — error re-appears but still exactly one instance (not stacked)
    await page.locator('button:has-text("Retry")').click();
    await expect(page.locator('text=Could not load content types')).toBeVisible({ timeout: 6000 });
    const errorCount2 = await page.locator('text=Could not load content types').count();
    expect(errorCount2).toBe(1);
  });

  // ── 09-06: Login form — submit button disabled during login request ─────────

  test('09-06: login submit button disabled while POST /api/v1/auth/login is in-flight', async ({ page }) => {
    await page.route('**/api/v1/auth/login', async (route) => {
      if (route.request().method() === 'POST') {
        await new Promise((r) => setTimeout(r, 500));
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            token: 'test-token',
            expires_at: '2099-01-01T00:00:00Z',
            email: 'admin@example.com',
            role: 'admin',
            org_id: null,
          }),
        });
      }
      return route.continue();
    });

    await page.goto('/login');
    await page.locator('input[type="email"]').fill('admin@example.com');
    await page.locator('input[type="password"]').fill('password');

    const submitBtn = page.locator('button[type="submit"]');
    await submitBtn.click();

    // Submit button should be disabled while request is in-flight
    await expect(submitBtn).toBeDisabled({ timeout: 2000 });
  });
});
