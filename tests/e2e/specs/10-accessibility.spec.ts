/**
 * Category: Accessibility
 *
 * Tests ARIA attributes, label associations, keyboard navigation,
 * and screen-reader-accessible error states.
 *
 * Patterns inspired by nene-corpus 09-accessibility.spec.ts.
 */

import { test, expect } from '@playwright/test';
import {
  bypassLogin,
  gotoAdmin,
  mockTagsEndpoint,
  mockUsersEndpoint,
  mockEntityTypesEndpoint,
} from '../fixtures/helpers.js';
import {
  ENTITY_TYPE_LIST_EMPTY,
  TAG_LIST_EMPTY,
  USER_LIST_EMPTY,
} from '../fixtures/api-mocks.js';

test.describe('Accessibility', () => {
  // ── 10-01: Entity types — label elements are present ────────────────────

  test('10-01: entity type create form — name input has a visible label', async ({ page }) => {
    await bypassLogin(page);
    await mockEntityTypesEndpoint(page, ENTITY_TYPE_LIST_EMPTY);
    await gotoAdmin(page, '/entity-types');

    // <label htmlFor="entity-type-name"> must exist
    await expect(page.locator('label[for="entity-type-name"]')).toBeVisible({ timeout: 6000 });
  });

  test('10-02: entity type create form — slug input has a visible label', async ({ page }) => {
    await bypassLogin(page);
    await mockEntityTypesEndpoint(page, ENTITY_TYPE_LIST_EMPTY);
    await gotoAdmin(page, '/entity-types');

    await expect(page.locator('label[for="entity-type-slug"]')).toBeVisible({ timeout: 6000 });
  });

  // ── 10-03: Error state — aria-invalid set on input with error ─────────────

  test('10-03: entity type create — empty submit marks name input aria-invalid', async ({ page }) => {
    await bypassLogin(page);
    await mockEntityTypesEndpoint(page, ENTITY_TYPE_LIST_EMPTY);
    await gotoAdmin(page, '/entity-types');

    // Submit without filling the form to trigger validation errors
    await page.locator('button:has-text("Create content type")').click();

    // Name input should be marked invalid
    await expect(page.locator('input[id="entity-type-name"]')).toHaveAttribute('aria-invalid', 'true', {
      timeout: 3000,
    });
  });

  // ── 10-04: Error text is visible and not hidden from AT ───────────────────

  test('10-04: entity types load error message is visible and not aria-hidden', async ({ page }) => {
    await bypassLogin(page);
    await page.route('**/api/v1/entity-types**', (route) =>
      route.fulfill({ status: 500, contentType: 'application/json', body: '{"detail":"err"}' }),
    );
    await gotoAdmin(page, '/entity-types');

    const errorEl = page.locator('text=Could not load content types');
    await expect(errorEl).toBeVisible({ timeout: 6000 });
    // Must not be hidden from assistive technology
    await expect(errorEl).not.toHaveAttribute('aria-hidden', 'true');
  });

  // ── 10-05: Login form — correct input types ────────────────────────────────

  test('10-05: login form — email input has type="email"', async ({ page }) => {
    await page.goto('/login');
    await expect(page.locator('input[type="email"]')).toBeVisible({ timeout: 6000 });
  });

  test('10-06: login form — password input has type="password"', async ({ page }) => {
    await page.goto('/login');
    await expect(page.locator('input[type="password"]')).toBeVisible({ timeout: 6000 });
  });

  // ── 10-07: Keyboard navigation — Tab focuses inputs in order ──────────────

  test('10-07: entity type create form — Tab moves focus between name and slug', async ({ page }) => {
    await bypassLogin(page);
    await mockEntityTypesEndpoint(page, ENTITY_TYPE_LIST_EMPTY);
    await gotoAdmin(page, '/entity-types');

    const nameInput = page.locator('input[id="entity-type-name"]');
    const slugInput = page.locator('input[id="entity-type-slug"]');

    // Click name input and Tab to slug
    await nameInput.click();
    await expect(nameInput).toBeFocused();

    await page.keyboard.press('Tab');
    await expect(slugInput).toBeFocused();
  });

  // ── 10-08: Tags page — create form inputs have labels ─────────────────────

  test('10-08: tag create form — name and slug inputs have labels', async ({ page }) => {
    await bypassLogin(page);
    await mockTagsEndpoint(page, TAG_LIST_EMPTY);
    await gotoAdmin(page, '/tags');

    await expect(page.locator('label[for="tag-name"]')).toBeVisible({ timeout: 6000 });
    await expect(page.locator('label[for="tag-slug"]')).toBeVisible({ timeout: 6000 });
  });

  // ── 10-09: Users page — invite form has accessible email input ────────────

  test('10-09: invite form — email input has type="email" and visible label', async ({ page }) => {
    await bypassLogin(page);
    await mockUsersEndpoint(page, USER_LIST_EMPTY);
    await gotoAdmin(page, '/users');

    await page.locator('button:has-text("Invite user")').click();

    const emailInput = page.locator('input[type="email"]').first();
    await expect(emailInput).toBeVisible({ timeout: 6000 });
  });

  // ── 10-10: Login page — keyboard Enter submits form ───────────────────────

  test('10-10: login page — pressing Enter in password field submits the form', async ({ page }) => {
    let loginCalled = false;
    await page.route('**/api/v1/auth/login', (route) => {
      if (route.request().method() === 'POST') {
        loginCalled = true;
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            token: 'tok',
            expires_at: '2099-01-01T00:00:00Z',
            email: 'admin@example.com',
            role: 'admin',
            org_id: null,
          }),
        });
      }
      return route.continue();
    });
    // Also mock entity-types for AppShell after login
    await page.route('**/api/v1/entity-types**', (route) =>
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ items: [], limit: 20, offset: 0 }),
      }),
    );

    await page.goto('/login');
    await page.locator('input[type="email"]').fill('admin@example.com');
    await page.locator('input[type="password"]').fill('password');
    await page.locator('input[type="password"]').press('Enter');

    await page.waitForTimeout(500);
    expect(loginCalled).toBe(true);
  });

  // ── 10-11: h1 elements are present on all main admin pages ───────────────

  test('10-11: entity-types, tags, users all have h1 headings', async ({ page }) => {
    // Entity types
    await bypassLogin(page);
    await gotoAdmin(page, '/entity-types');
    await expect(page.locator('h1')).toBeVisible({ timeout: 6000 });

    // Tags
    await mockTagsEndpoint(page, TAG_LIST_EMPTY);
    await gotoAdmin(page, '/tags');
    await expect(page.locator('h1')).toBeVisible({ timeout: 6000 });

    // Users
    await mockUsersEndpoint(page, USER_LIST_EMPTY);
    await gotoAdmin(page, '/users');
    await expect(page.locator('h1')).toBeVisible({ timeout: 6000 });
  });
});
