/**
 * Category: Users Management
 *
 * Tests user list, invite flow, and access control.
 */

import { test, expect } from '@playwright/test';
import {
  bypassLogin,
  gotoAdmin,
  mockUsersEndpoint,
} from '../fixtures/helpers.js';
import { USER_LIST_EMPTY, USER_LIST, USER_LIST_WITH_ORG, INVITE_USER_RESPONSE_WITH_ORG } from '../fixtures/api-mocks.js';

test.describe('Users', () => {
  test('05-01: page title — "Users"', async ({ page }) => {
    await bypassLogin(page);
    await mockUsersEndpoint(page, USER_LIST_EMPTY);
    await gotoAdmin(page, '/users');

    await expect(page.locator('h1')).toContainText('Users');
  });

  test('05-02: empty state — shows "No users yet"', async ({ page }) => {
    await bypassLogin(page);
    await mockUsersEndpoint(page, USER_LIST_EMPTY);
    await gotoAdmin(page, '/users');

    await expect(page.locator('text=No users yet')).toBeVisible({ timeout: 6000 });
  });

  test('05-03: user list — email and role shown', async ({ page }) => {
    await bypassLogin(page);
    await mockUsersEndpoint(page, USER_LIST);
    await gotoAdmin(page, '/users');

    await expect(page.locator('text=admin@example.com').first()).toBeVisible({ timeout: 6000 });
    await expect(page.locator('text=editor@example.com').first()).toBeVisible({ timeout: 6000 });
  });

  test('05-04: invite button is visible for admin', async ({ page }) => {
    await bypassLogin(page);
    await mockUsersEndpoint(page, USER_LIST_EMPTY);
    await gotoAdmin(page, '/users');

    await expect(page.locator('button:has-text("Invite user")')).toBeVisible({ timeout: 6000 });
  });

  test('05-05: invite form opens on button click', async ({ page }) => {
    await bypassLogin(page);
    await mockUsersEndpoint(page, USER_LIST_EMPTY);
    await gotoAdmin(page, '/users');

    await page.locator('button:has-text("Invite user")').click();

    // Email input should appear in invite form
    await expect(page.locator('input[type="email"]')).toBeVisible({ timeout: 6000 });
  });

  test('05-06: invite user — calls POST /api/v1/users/invite', async ({ page }) => {
    await bypassLogin(page);
    await mockUsersEndpoint(page, USER_LIST_EMPTY);

    let postCalled = false;
    await page.route('**/api/v1/users/invite', (route) => {
      if (route.request().method() === 'POST') {
        postCalled = true;
        return route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify({
            id: 3,
            email: 'newuser@example.com',
            role: 'editor',
            status: 'invited',
          }),
        });
      }
      return route.continue();
    });

    await gotoAdmin(page, '/users');
    await page.locator('button:has-text("Invite user")').click();

    // Fill invite form — use pressSequentially to properly trigger react-hook-form onChange
    const emailInput = page.locator('input[type="email"]').first();
    await emailInput.click();
    await emailInput.pressSequentially('newuser@example.com');
    await page.locator('button[type="submit"]:has-text("Send invitation")').click();

    await page.waitForTimeout(500);
    expect(postCalled).toBe(true);
  });

  test('05-07: editor role — redirected to /forbidden', async ({ page }) => {
    await bypassLogin(page, { role: 'editor' });
    await gotoAdmin(page, '/users');

    await expect(page).toHaveURL(/\/forbidden/, { timeout: 6000 });
  });

  test('05-08: load error — shows error message with retry', async ({ page }) => {
    await bypassLogin(page);
    await page.route('**/api/v1/users**', (route) =>
      route.fulfill({ status: 500, contentType: 'application/json', body: '{"detail":"Error"}' }),
    );
    await gotoAdmin(page, '/users');

    await expect(page.locator('text=Could not load users')).toBeVisible({ timeout: 6000 });
  });

  // ── Level 2: Multi-tenancy single-feature ─────────────────────────────────────

  test('05-09: org-augmented user list — page renders correctly with organization_id in response', async ({ page }) => {
    await bypassLogin(page, { orgId: 1 });
    await mockUsersEndpoint(page, USER_LIST_WITH_ORG);
    await gotoAdmin(page, '/users');

    // org フィールドを含む API レスポンスでも email/role が正しく描画される
    await expect(page.locator('text=admin@acme.example.com').first()).toBeVisible({ timeout: 6000 });
    await expect(page.locator('text=editor@acme.example.com').first()).toBeVisible({ timeout: 6000 });
  });

  test('05-10: invite user with org context — POST /api/v1/users/invite called with org_id in response', async ({ page }) => {
    await bypassLogin(page, { orgId: 1 });
    await mockUsersEndpoint(page, USER_LIST_EMPTY);

    let postBody: unknown = null;
    await page.route('**/api/v1/users/invite', (route) => {
      if (route.request().method() === 'POST') {
        postBody = route.request().postDataJSON();
        return route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify(INVITE_USER_RESPONSE_WITH_ORG),
        });
      }
      return route.continue();
    });

    await gotoAdmin(page, '/users');
    await page.locator('button:has-text("Invite user")').click();

    const emailInput = page.locator('input[type="email"]').first();
    await emailInput.click();
    await emailInput.pressSequentially('newmember@acme.example.com');
    await page.locator('button[type="submit"]:has-text("Send invitation")').click();

    await page.waitForTimeout(500);
    expect(postBody).not.toBeNull();
  });

  test('05-11: superadmin role — can access users page (no /forbidden redirect)', async ({ page }) => {
    await bypassLogin(page, { role: 'superadmin' });
    await mockUsersEndpoint(page, USER_LIST_EMPTY);
    await gotoAdmin(page, '/users');

    // superadmin はユーザー管理にアクセスできる（/forbidden に遷移しない）
    await expect(page).not.toHaveURL(/\/forbidden/, { timeout: 6000 });
    await expect(page.locator('h1')).toContainText('Users', { timeout: 6000 });
  });
});
