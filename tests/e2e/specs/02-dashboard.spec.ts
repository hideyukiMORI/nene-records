/**
 * Category: Dashboard (Home)
 *
 * Tests the admin dashboard rendering with various data states.
 */

import { test, expect } from '@playwright/test';
import {
  bypassLogin,
  gotoAdmin,
  mockDashboard,
} from '../fixtures/helpers.js';
import { DASHBOARD_EMPTY, DASHBOARD_WITH_CONTENT } from '../fixtures/api-mocks.js';

test.describe('Dashboard', () => {
  test('02-01: dashboard page title is shown', async ({ page }) => {
    await bypassLogin(page);
    await mockDashboard(page, DASHBOARD_EMPTY);
    await gotoAdmin(page);

    await expect(page.locator('h1')).toContainText('Dashboard');
  });

  test('02-02: sidebar navigation is visible after login', async ({ page }) => {
    await bypassLogin(page);
    await mockDashboard(page, DASHBOARD_EMPTY);
    await gotoAdmin(page);

    // Sidebar should have key nav links
    await expect(page.locator('nav a[href="/admin"]')).toBeVisible();
  });

  test('02-03: dashboard shows content summary when data exists', async ({ page }) => {
    await bypassLogin(page);
    await mockDashboard(page, DASHBOARD_WITH_CONTENT);
    await gotoAdmin(page);

    // Content summary should show entity type names
    await expect(page.locator('text=Posts')).toBeVisible({ timeout: 6000 });
    await expect(page.locator('text=Pages')).toBeVisible({ timeout: 6000 });
  });

  test('02-04: dashboard shows error message when API fails', async ({ page }) => {
    await bypassLogin(page);
    await page.route('**/api/v1/dashboard', (route) =>
      route.fulfill({ status: 500, contentType: 'application/json', body: JSON.stringify({ detail: 'Error' }) }),
    );
    await gotoAdmin(page);

    await expect(page.locator('text=Could not load dashboard')).toBeVisible({ timeout: 6000 });
  });

  test('02-05: sign-out button is visible in header', async ({ page }) => {
    await bypassLogin(page);
    await mockDashboard(page, DASHBOARD_EMPTY);
    await gotoAdmin(page);

    // The logout button is an icon-only button with aria-label="Log out"
    await expect(page.locator('button[aria-label="Log out"]')).toBeVisible();
  });

  test('02-06: admin email shown in header after login', async ({ page }) => {
    await bypassLogin(page, { email: 'admin@example.com' });
    await mockDashboard(page, DASHBOARD_EMPTY);
    await gotoAdmin(page);

    // Email is shown in the sidebar (aside), not the mobile top bar (header)
    await expect(page.locator('aside')).toContainText('admin@example.com');
  });
});
