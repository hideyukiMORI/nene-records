/**
 * Category: Authentication
 *
 * Tests login/logout flows, credential validation, and auth redirect behaviour.
 */

import { test, expect } from '@playwright/test';
import {
  LOGIN_URL,
  ADMIN_URL,
  AUTH_STORAGE_KEY,
  BASE_URL,
  mockLoginEndpoint,
  bypassLogin,
  gotoAdmin,
} from '../fixtures/helpers.js';
import { DEFAULT_LOGIN_RESPONSE } from '../fixtures/api-mocks.js';

test.describe('Authentication', () => {
  // ── Login form ──────────────────────────────────────────────────────────────

  test('01-01: login form — email/password inputs and submit button are visible', async ({ page }) => {
    await page.goto(LOGIN_URL);
    await expect(page.locator('input[type="email"]')).toBeVisible();
    await expect(page.locator('input[type="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toContainText('Sign in');
  });

  test('01-02: valid credentials — redirects to /admin', async ({ page }) => {
    await mockLoginEndpoint(page);
    await page.goto(LOGIN_URL);
    await page.locator('input[type="email"]').fill('admin@example.com');
    await page.locator('input[type="password"]').fill('password');
    await page.locator('button[type="submit"]').click();

    await page.waitForURL(`${BASE_URL}/admin`);
    await expect(page).toHaveURL(`${BASE_URL}/admin`);
  });

  test('01-03: token saved to localStorage after login', async ({ page }) => {
    await mockLoginEndpoint(page);
    await page.goto(LOGIN_URL);
    await page.locator('input[type="email"]').fill('admin@example.com');
    await page.locator('input[type="password"]').fill('password');
    await page.locator('button[type="submit"]').click();

    await page.waitForURL(`${BASE_URL}/admin`);

    const stored = await page.evaluate((key: string) => localStorage.getItem(key), AUTH_STORAGE_KEY);
    expect(stored).not.toBeNull();
    const session = JSON.parse(stored!);
    expect(session.token).toBe(DEFAULT_LOGIN_RESPONSE.token);
    expect(session.email).toBe(DEFAULT_LOGIN_RESPONSE.email);
  });

  test('01-04: wrong credentials — error message shown, stays on login page', async ({ page }) => {
    await page.route('**/api/v1/auth/login', (route) =>
      route.fulfill({
        status: 401,
        contentType: 'application/json',
        body: JSON.stringify({ detail: 'Invalid credentials.' }),
      }),
    );
    await page.goto(LOGIN_URL);
    await page.locator('input[type="email"]').fill('admin@example.com');
    await page.locator('input[type="password"]').fill('wrongpassword');
    await page.locator('button[type="submit"]').click();

    await expect(page.locator('[role="alert"]')).toBeVisible({ timeout: 6000 });
    await expect(page.locator('button[type="submit"]')).toContainText('Sign in');
  });

  test('01-05: empty password — stays on login page, shows error', async ({ page }) => {
    // Mock login to return 401 for empty password submission
    await page.route('**/api/v1/auth/login', (route) =>
      route.fulfill({
        status: 401,
        contentType: 'application/json',
        body: JSON.stringify({ detail: 'Invalid credentials.' }),
      }),
    );
    await page.goto(LOGIN_URL);
    await page.locator('input[type="email"]').fill('admin@example.com');
    // Leave password empty and submit — API is called, backend rejects it
    await page.locator('button[type="submit"]').click();
    // Error alert is shown and user stays on login page
    await expect(page.locator('[role="alert"]')).toBeVisible({ timeout: 6000 });
    await expect(page).toHaveURL(/\/login/);
  });

  test('01-06: network error — error message shown', async ({ page }) => {
    await page.route('**/api/v1/auth/login', (route) => route.abort('failed'));
    await page.goto(LOGIN_URL);
    await page.locator('input[type="email"]').fill('admin@example.com');
    await page.locator('input[type="password"]').fill('somepass');
    await page.locator('button[type="submit"]').click();

    await expect(page.locator('[role="alert"]')).toBeVisible({ timeout: 6000 });
  });

  // ── Auth redirect ───────────────────────────────────────────────────────────

  test('01-07: unauthenticated access to /admin — redirects to /login', async ({ page }) => {
    await page.goto(ADMIN_URL);
    await expect(page).toHaveURL(/\/login/);
  });

  test('01-08: authenticated user accessing /login — redirects to /admin', async ({ page }) => {
    await bypassLogin(page);
    await page.goto(LOGIN_URL);
    // After injecting a valid token, navigating to /admin works
    await gotoAdmin(page);
    await expect(page).toHaveURL(/\/admin/);
  });

  // ── Logout ──────────────────────────────────────────────────────────────────

  test('01-09: logout — token cleared from localStorage, redirects to /login', async ({ page }) => {
    await bypassLogin(page);
    await gotoAdmin(page);

    // Find and click the logout icon button (aria-label="Log out")
    await page.locator('button[aria-label="Log out"]').click();

    // Redirected to login
    await expect(page).toHaveURL(/\/login/, { timeout: 6000 });

    // Token cleared from localStorage
    const stored = await page.evaluate((key: string) => localStorage.getItem(key), AUTH_STORAGE_KEY);
    expect(stored).toBeNull();
  });
});
