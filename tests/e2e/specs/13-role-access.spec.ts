/**
 * Category: Role-Based Access Control Matrix
 *
 * Systematically verifies which admin pages are accessible by each role
 * (admin, editor, superadmin) and that the correct UI elements are
 * shown or hidden based on capabilities.
 *
 * Inspired by nene-corpus persona-parameterized tests and the
 * access matrix in 08-multitenancy-full.spec.ts.
 *
 * Role capabilities:
 *  superadmin — all capabilities
 *  admin      — all except manage_organizations
 *  editor     — read_settings, edit_content only
 */

import { test, expect } from '@playwright/test';
import {
  bypassLogin,
  gotoAdmin,
  gotoSuperadmin,
  mockUsersEndpoint,
  mockTagsEndpoint,
  mockEntityTypesEndpoint,
  mockOrganizationsEndpoint,
} from '../fixtures/helpers.js';
import {
  ENTITY_TYPE_LIST_EMPTY,
  TAG_LIST_EMPTY,
  USER_LIST_EMPTY,
  ORGANIZATION_LIST_EMPTY,
} from '../fixtures/api-mocks.js';

// Helper to mock all pages' GET endpoints
async function mockAllAdminEndpoints(page: import('@playwright/test').Page): Promise<void> {
  await page.route('**/api/v1/tags**', (route) => {
    if (route.request().method() === 'GET') {
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(TAG_LIST_EMPTY) });
    }
    return route.continue();
  });
  await page.route('**/api/v1/users**', (route) => {
    if (route.request().method() === 'GET') {
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(USER_LIST_EMPTY) });
    }
    return route.continue();
  });
  await page.route('**/api/v1/admin/comments**', (route) => {
    if (route.request().method() === 'GET') {
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ items: [] }) });
    }
    return route.continue();
  });
  await page.route('**/api/v1/navigation-items**', (route) => {
    if (route.request().method() === 'GET') {
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ items: [] }) });
    }
    return route.continue();
  });
  await page.route('**/api/v1/media**', (route) => {
    if (route.request().method() === 'GET') {
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ items: [] }) });
    }
    return route.continue();
  });
  await page.route('**/api/v1/settings**', (route) => {
    if (route.request().method() === 'GET') {
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ items: [] }) });
    }
    return route.continue();
  });
  await page.route('**/api/v1/webhooks**', (route) => {
    if (route.request().method() === 'GET') {
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ items: [] }) });
    }
    return route.continue();
  });
}

test.describe('Role Access Matrix', () => {
  // ══════════════════════════════════════════════════════════════════════
  //  ADMIN role — can access all admin pages
  // ══════════════════════════════════════════════════════════════════════

  test('13-01: admin → entity-types: accessible with create form visible', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await mockEntityTypesEndpoint(page, ENTITY_TYPE_LIST_EMPTY);
    await gotoAdmin(page, '/entity-types');

    await expect(page).not.toHaveURL(/\/forbidden/, { timeout: 6000 });
    await expect(page.locator('input[id="entity-type-name"]')).toBeVisible({ timeout: 6000 });
  });

  test('13-02: admin → tags: accessible, create form visible', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await mockTagsEndpoint(page, TAG_LIST_EMPTY);
    await gotoAdmin(page, '/tags');

    await expect(page).not.toHaveURL(/\/forbidden/, { timeout: 6000 });
    await expect(page.locator('input[id="tag-name"]')).toBeVisible({ timeout: 6000 });
  });

  test('13-03: admin → users: accessible, Invite user button visible', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await mockUsersEndpoint(page, USER_LIST_EMPTY);
    await gotoAdmin(page, '/users');

    await expect(page).not.toHaveURL(/\/forbidden/, { timeout: 6000 });
    await expect(page.locator('button:has-text("Invite user")')).toBeVisible({ timeout: 6000 });
  });

  test('13-04: admin → comments: accessible (h1 "Comments")', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await mockAllAdminEndpoints(page);
    await gotoAdmin(page, '/comments');

    await expect(page).not.toHaveURL(/\/forbidden/, { timeout: 6000 });
    await expect(page.locator('h1')).toContainText('Comments', { timeout: 6000 });
  });

  test('13-05: admin → navigation (Menus): accessible', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await mockAllAdminEndpoints(page);
    await gotoAdmin(page, '/navigation');

    await expect(page).not.toHaveURL(/\/forbidden/, { timeout: 6000 });
    await expect(page.locator('h1')).toContainText('Menus', { timeout: 6000 });
  });

  test('13-06: admin → media: accessible (h1 "Media library")', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await mockAllAdminEndpoints(page);
    await gotoAdmin(page, '/media');

    await expect(page).not.toHaveURL(/\/forbidden/, { timeout: 6000 });
    await expect(page.locator('h1')).toContainText('Media library', { timeout: 6000 });
  });

  test('13-07: admin → settings: accessible (h1 "Site settings")', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await mockAllAdminEndpoints(page);
    await gotoAdmin(page, '/settings');

    await expect(page).not.toHaveURL(/\/forbidden/, { timeout: 6000 });
    await expect(page.locator('h1')).toContainText('Site settings', { timeout: 6000 });
  });

  test('13-08: admin → superadmin/organizations: redirected to /forbidden', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await gotoSuperadmin(page, '/organizations');

    await expect(page).toHaveURL(/\/forbidden/, { timeout: 6000 });
  });

  // ══════════════════════════════════════════════════════════════════════
  //  EDITOR role — limited access
  // ══════════════════════════════════════════════════════════════════════

  test('13-09: editor → entity-types: page accessible but NO create form', async ({ page }) => {
    await bypassLogin(page, { role: 'editor' });
    await mockEntityTypesEndpoint(page, ENTITY_TYPE_LIST_EMPTY);
    await gotoAdmin(page, '/entity-types');

    // Page loads (no redirect)
    await expect(page).not.toHaveURL(/\/forbidden/, { timeout: 6000 });
    // Create form is hidden (editor lacks manage_schema)
    await expect(page.locator('input[id="entity-type-name"]')).not.toBeVisible({ timeout: 6000 });
  });

  test('13-10: editor → tags: redirected to /forbidden', async ({ page }) => {
    await bypassLogin(page, { role: 'editor' });
    await gotoAdmin(page, '/tags');

    await expect(page).toHaveURL(/\/forbidden/, { timeout: 6000 });
  });

  test('13-11: editor → users: redirected to /forbidden', async ({ page }) => {
    await bypassLogin(page, { role: 'editor' });
    await gotoAdmin(page, '/users');

    await expect(page).toHaveURL(/\/forbidden/, { timeout: 6000 });
  });

  test('13-12: editor → comments: redirected to /forbidden', async ({ page }) => {
    await bypassLogin(page, { role: 'editor' });
    await gotoAdmin(page, '/comments');

    await expect(page).toHaveURL(/\/forbidden/, { timeout: 6000 });
  });

  test('13-13: editor → navigation: redirected to /forbidden', async ({ page }) => {
    await bypassLogin(page, { role: 'editor' });
    await gotoAdmin(page, '/navigation');

    await expect(page).toHaveURL(/\/forbidden/, { timeout: 6000 });
  });

  test('13-14: editor → media: redirected to /forbidden', async ({ page }) => {
    await bypassLogin(page, { role: 'editor' });
    await gotoAdmin(page, '/media');

    await expect(page).toHaveURL(/\/forbidden/, { timeout: 6000 });
  });

  test('13-15: editor → field-defs (manage_schema page): redirected to /forbidden', async ({ page }) => {
    await bypassLogin(page, { role: 'editor' });
    // FieldDefsPage redirects when !canManageSchema
    await gotoAdmin(page, '/entity-types/posts/fields');

    await expect(page).toHaveURL(/\/forbidden/, { timeout: 6000 });
  });

  // ══════════════════════════════════════════════════════════════════════
  //  SUPERADMIN role — all access including manage_organizations
  // ══════════════════════════════════════════════════════════════════════

  test('13-16: superadmin → entity-types: accessible with create form', async ({ page }) => {
    await bypassLogin(page, { role: 'superadmin' });
    await mockEntityTypesEndpoint(page, ENTITY_TYPE_LIST_EMPTY);
    await gotoAdmin(page, '/entity-types');

    await expect(page).not.toHaveURL(/\/forbidden/, { timeout: 6000 });
    await expect(page.locator('input[id="entity-type-name"]')).toBeVisible({ timeout: 6000 });
  });

  test('13-17: superadmin → users: accessible', async ({ page }) => {
    await bypassLogin(page, { role: 'superadmin' });
    await mockUsersEndpoint(page, USER_LIST_EMPTY);
    await gotoAdmin(page, '/users');

    await expect(page).not.toHaveURL(/\/forbidden/, { timeout: 6000 });
    await expect(page.locator('h1')).toContainText('Users', { timeout: 6000 });
  });

  test('13-18: superadmin → superadmin/organizations: accessible', async ({ page }) => {
    await bypassLogin(page, { role: 'superadmin' });
    await mockOrganizationsEndpoint(page, ORGANIZATION_LIST_EMPTY);
    await gotoSuperadmin(page, '/organizations');

    await expect(page).not.toHaveURL(/\/forbidden/, { timeout: 6000 });
    await expect(page.locator('h1')).toBeVisible({ timeout: 6000 });
  });

  test('13-19: superadmin → tags: accessible, create form visible', async ({ page }) => {
    await bypassLogin(page, { role: 'superadmin' });
    await mockTagsEndpoint(page, TAG_LIST_EMPTY);
    await gotoAdmin(page, '/tags');

    await expect(page).not.toHaveURL(/\/forbidden/, { timeout: 6000 });
    await expect(page.locator('input[id="tag-name"]')).toBeVisible({ timeout: 6000 });
  });

  test('13-20: superadmin → comments: accessible', async ({ page }) => {
    await bypassLogin(page, { role: 'superadmin' });
    await mockAllAdminEndpoints(page);
    await gotoAdmin(page, '/comments');

    await expect(page).not.toHaveURL(/\/forbidden/, { timeout: 6000 });
    await expect(page.locator('h1')).toContainText('Comments', { timeout: 6000 });
  });
});
