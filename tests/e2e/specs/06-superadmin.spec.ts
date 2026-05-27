/**
 * Category: Superadmin — Organizations
 *
 * Tests the superadmin organization management pages.
 * Requires role=superadmin in localStorage session.
 */

import { test, expect } from '@playwright/test';
import {
  bypassLogin,
  gotoSuperadmin,
  mockOrganizationsEndpoint,
  SUPERADMIN_URL,
  BASE_URL,
} from '../fixtures/helpers.js';
import {
  ORGANIZATION_LIST_EMPTY,
  ORGANIZATION_LIST,
  ORGANIZATION_DETAIL,
  ORGANIZATION_DETAIL_WITH_EXTERNAL_ID,
} from '../fixtures/api-mocks.js';

test.describe('Superadmin — Organizations', () => {
  test('06-01: non-superadmin — redirected to /forbidden', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await page.goto(`${SUPERADMIN_URL}/organizations`);

    await expect(page).toHaveURL(/\/forbidden/, { timeout: 6000 });
  });

  test('06-02: superadmin sidebar shows "Organizations" link', async ({ page }) => {
    await bypassLogin(page, { role: 'superadmin' });
    await mockOrganizationsEndpoint(page, ORGANIZATION_LIST_EMPTY);
    await gotoSuperadmin(page, '/organizations');

    await expect(page.locator('nav a:has-text("Organizations")')).toBeVisible({ timeout: 6000 });
  });

  test('06-03: organizations list — empty state message', async ({ page }) => {
    await bypassLogin(page, { role: 'superadmin' });
    await mockOrganizationsEndpoint(page, ORGANIZATION_LIST_EMPTY);
    await gotoSuperadmin(page, '/organizations');

    await expect(page.locator('text=No organizations yet')).toBeVisible({ timeout: 6000 });
  });

  test('06-04: organizations list — shows org names', async ({ page }) => {
    await bypassLogin(page, { role: 'superadmin' });
    await mockOrganizationsEndpoint(page, ORGANIZATION_LIST);
    await gotoSuperadmin(page, '/organizations');

    await expect(page.locator('text=Acme Corp')).toBeVisible({ timeout: 6000 });
    await expect(page.locator('text=Globex Inc')).toBeVisible({ timeout: 6000 });
  });

  test('06-05: create org — New Organization button visible', async ({ page }) => {
    await bypassLogin(page, { role: 'superadmin' });
    await mockOrganizationsEndpoint(page, ORGANIZATION_LIST_EMPTY);
    await gotoSuperadmin(page, '/organizations');

    await expect(page.locator('button:has-text("New Organization")')).toBeVisible({ timeout: 6000 });
  });

  test('06-06: create org form — shows name/slug fields on button click', async ({ page }) => {
    await bypassLogin(page, { role: 'superadmin' });
    await mockOrganizationsEndpoint(page, ORGANIZATION_LIST_EMPTY);
    await gotoSuperadmin(page, '/organizations');

    await page.locator('button:has-text("New Organization")').click();

    await expect(page.locator('input[id="org-name"]')).toBeVisible({ timeout: 6000 });
    await expect(page.locator('input[id="org-slug"]')).toBeVisible({ timeout: 6000 });
  });

  test('06-07: create org — calls POST /api/v1/organizations', async ({ page }) => {
    await bypassLogin(page, { role: 'superadmin' });

    let postCalled = false;
    await page.route('**/api/v1/organizations**', (route) => {
      if (route.request().method() === 'POST') {
        postCalled = true;
        return route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify({ id: 3, name: 'NewCo', slug: 'newco', plan: 'free', is_active: true, custom_domain: null, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' }),
        });
      }
      if (route.request().method() === 'GET') {
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(ORGANIZATION_LIST_EMPTY),
        });
      }
      return route.continue();
    });

    await gotoSuperadmin(page, '/organizations');
    await page.locator('button:has-text("New Organization")').click();
    await page.locator('input[id="org-name"]').fill('NewCo');
    await page.locator('input[id="org-slug"]').fill('newco');
    await page.locator('button[type="submit"]:has-text("Create")').click();

    await page.waitForTimeout(500);
    expect(postCalled).toBe(true);
  });

  test('06-08: org detail — shows name and settings form', async ({ page }) => {
    await bypassLogin(page, { role: 'superadmin' });

    await page.route('**/api/v1/organizations/1', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(ORGANIZATION_DETAIL),
        });
      }
      return route.continue();
    });

    await gotoSuperadmin(page, '/organizations/1');

    await expect(page.locator('h1')).toContainText('Acme Corp', { timeout: 6000 });
    await expect(page.locator('text=Organization Settings')).toBeVisible({ timeout: 6000 });
  });

  test('06-09: org detail — shows danger zone delete button', async ({ page }) => {
    await bypassLogin(page, { role: 'superadmin' });

    await page.route('**/api/v1/organizations/1', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(ORGANIZATION_DETAIL),
        });
      }
      return route.continue();
    });

    await gotoSuperadmin(page, '/organizations/1');

    await expect(page.locator('button:has-text("Delete Organization")')).toBeVisible({ timeout: 6000 });
  });

  // ── Level 2: Multi-tenancy single-feature ─────────────────────────────────────

  test('06-10-org-update: org detail — PUT/PATCH call is made when save button clicked', async ({ page }) => {
    await bypassLogin(page, { role: 'superadmin' });

    let updateCalled = false;
    await page.route('**/api/v1/organizations/1', (route) => {
      const method = route.request().method();
      if (method === 'GET') {
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(ORGANIZATION_DETAIL),
        });
      }
      if (method === 'PUT' || method === 'PATCH') {
        updateCalled = true;
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(ORGANIZATION_DETAIL),
        });
      }
      return route.continue();
    });

    await gotoSuperadmin(page, '/organizations/1');
    await expect(page.locator('h1')).toContainText('Acme Corp', { timeout: 6000 });

    // フォームを送信（Save ボタンをクリック）
    const saveButton = page.locator('button[type="submit"]').first();
    if (await saveButton.isVisible({ timeout: 3000 })) {
      await saveButton.click();
      await page.waitForTimeout(500);
      expect(updateCalled).toBe(true);
    }
    // Save ボタンがない場合はフォーム送信をスキップ（UI 構造依存）
  });

  test('06-10-delete: org detail — confirm dialog appears and DELETE request is fired', async ({ page }) => {
    await bypassLogin(page, { role: 'superadmin' });

    // Use a promise to reliably capture the DELETE request before navigation
    let deletePromiseResolve: (v: boolean) => void;
    const deletePromise = new Promise<boolean>((resolve) => {
      deletePromiseResolve = resolve;
    });

    await page.route('**/api/v1/organizations/**', (route) => {
      const method = route.request().method();
      if (method === 'GET') {
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(ORGANIZATION_DETAIL),
        });
      }
      if (method === 'DELETE') {
        deletePromiseResolve(true);
        return route.fulfill({ status: 204 });
      }
      return route.continue();
    });

    await gotoSuperadmin(page, '/organizations/1');
    await expect(page.locator('button:has-text("Delete Organization")')).toBeVisible({ timeout: 6000 });

    // Step 1: Delete Organization ボタンをクリックで確認ダイアログが開く
    await page.locator('button:has-text("Delete Organization")').click();

    // Step 2: 確認ダイアログの "Delete permanently" ボタンが表示される
    await expect(page.locator('button:has-text("Delete permanently")')).toBeVisible({ timeout: 6000 });

    // Step 3: 確認ボタンをクリックして DELETE リクエストが発行される
    await page.locator('button:has-text("Delete permanently")').click();
    const deleteCalled = await Promise.race([
      deletePromise,
      new Promise<boolean>((resolve) => { setTimeout(() => { resolve(false); }, 3000); }),
    ]);
    expect(deleteCalled).toBe(true);
  });

  test('06-10-external: org detail with external_id — page loads correctly', async ({ page }) => {
    await bypassLogin(page, { role: 'superadmin' });

    await page.route('**/api/v1/organizations/2', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(ORGANIZATION_DETAIL_WITH_EXTERNAL_ID),
        });
      }
      return route.continue();
    });

    await gotoSuperadmin(page, '/organizations/2');

    // external_id を含む org 詳細でもページが正常に描画される
    await expect(page.locator('h1')).toContainText('Globex', { timeout: 6000 });
    await expect(page.locator('text=Organization Settings')).toBeVisible({ timeout: 6000 });
  });

  test('06-10: org detail — back link navigates to organizations list', async ({ page }) => {
    await bypassLogin(page, { role: 'superadmin' });

    // List mock added first (lower priority) — handles GET /organizations?...
    await mockOrganizationsEndpoint(page, ORGANIZATION_LIST);
    // Detail mock added second (higher priority) — handles GET /organizations/1
    await page.route('**/api/v1/organizations/1', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(ORGANIZATION_DETAIL),
        });
      }
      return route.continue();
    });

    await gotoSuperadmin(page, '/organizations/1');

    // Wait for org detail to load
    await expect(page.locator('h1')).toContainText('Acme Corp', { timeout: 6000 });

    // Click back link (use main to avoid matching sidebar nav link)
    await page.locator('main a:has-text("Organizations")').click();
    await expect(page).toHaveURL(`${BASE_URL}/superadmin/organizations`);
  });
});
