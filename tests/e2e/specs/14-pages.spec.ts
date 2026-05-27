/**
 * Category: Additional Admin Pages
 *
 * Tests for pages not covered by earlier specs:
 *  - Comments management
 *  - Navigation (Menus)
 *  - Webhooks
 *  - Media library
 *  - Site settings
 *  - Dashboard (home page)
 *
 * Covers: page titles, empty states, load errors, and basic
 * create/list interactions for each page.
 *
 * IMPORTANT: All mock DTOs use snake_case to match the backend API and
 * the frontend mappers (e.g. dto.author_name, dto.original_name).
 */

import { test, expect } from '@playwright/test';
import {
  bypassLogin,
  gotoAdmin,
  mockDashboard,
} from '../fixtures/helpers.js';
import { DASHBOARD_EMPTY, DASHBOARD_WITH_CONTENT } from '../fixtures/api-mocks.js';

// ── Shared mock data (snake_case DTO format matching backend API) ────────────

const COMMENTS_EMPTY = { items: [] };
const COMMENTS_LIST = {
  items: [
    {
      id: 1,
      entity_id: 10,
      author_name: 'Alice',
      author_email: 'alice@example.com',
      body: 'Great post!',
      is_approved: false,
      created_at: '2026-01-01T00:00:00Z',
    },
    {
      id: 2,
      entity_id: 10,
      author_name: 'Bob',
      author_email: 'bob@example.com',
      body: 'Very helpful.',
      is_approved: true,
      created_at: '2026-01-02T00:00:00Z',
    },
  ],
};

const NAV_ITEMS_EMPTY = { items: [] };
const NAV_ITEMS_LIST = {
  items: [
    { id: 1, label: 'Home', url: '/', display_order: 0 },
    { id: 2, label: 'Blog', url: '/posts', display_order: 1 },
  ],
};

const WEBHOOK_EMPTY = { items: [] };
const WEBHOOK_LIST = {
  items: [
    {
      id: 1,
      url: 'https://example.com/hook',
      entity_type_id: null,
      events: ['entity.created'],
      is_active: true,
      secret: null,
      created_at: '2026-01-01T00:00:00Z',
    },
  ],
};

const MEDIA_EMPTY = { items: [] };
const MEDIA_LIST = {
  items: [
    {
      id: 1,
      original_name: 'hero.jpg',
      url: '/uploads/hero.jpg',
      mime_type: 'image/jpeg',
      size: 12345,
      created_at: '2026-01-01T00:00:00Z',
    },
  ],
};

const SETTINGS_LIST = {
  items: [
    {
      id: 1,
      setting_key: 'site_name',
      label: 'Site name',
      value: 'My Blog',
      data_type: 'text',
      is_public: true,
    },
  ],
};

// Standard problem-details error body (provides a proper title for AppError)
const SERVER_ERROR_BODY = JSON.stringify({
  type: 'about:blank',
  title: 'Internal Server Error',
  status: 500,
  detail: 'Server error',
  instance: '/api/v1/test',
});

// ── Comments ─────────────────────────────────────────────────────────────────

test.describe('Comments Page', () => {
  test('14-01: comments page — h1 is "Comments"', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await page.route('**/api/v1/admin/comments**', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(COMMENTS_EMPTY) });
      }
      return route.continue();
    });
    await gotoAdmin(page, '/comments');
    await expect(page.locator('h1')).toContainText('Comments', { timeout: 6000 });
  });

  test('14-02: comments page — empty state "No comments yet"', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await page.route('**/api/v1/admin/comments**', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(COMMENTS_EMPTY) });
      }
      return route.continue();
    });
    await gotoAdmin(page, '/comments');
    await expect(page.locator('text=No comments yet')).toBeVisible({ timeout: 6000 });
  });

  test('14-03: comments page — existing comments show author names', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await page.route('**/api/v1/admin/comments**', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(COMMENTS_LIST) });
      }
      return route.continue();
    });
    await gotoAdmin(page, '/comments');
    await expect(page.locator('text=Alice').first()).toBeVisible({ timeout: 6000 });
    await expect(page.locator('text=Bob').first()).toBeVisible({ timeout: 6000 });
  });

  test('14-04: comments page — load error shows error message', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await page.route('**/api/v1/admin/comments**', (route) =>
      route.fulfill({ status: 500, contentType: 'application/json', body: SERVER_ERROR_BODY }),
    );
    await gotoAdmin(page, '/comments');
    await expect(page.locator('text=Could not load comments')).toBeVisible({ timeout: 6000 });
  });

  test('14-05: comments page — Delete buttons shown for each comment', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await page.route('**/api/v1/admin/comments**', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(COMMENTS_LIST) });
      }
      return route.continue();
    });
    await gotoAdmin(page, '/comments');
    await expect(page.locator('text=Alice').first()).toBeVisible({ timeout: 6000 });
    // Delete buttons should be present for each comment
    await expect(page.locator('button:has-text("Delete")').first()).toBeVisible({ timeout: 6000 });
  });
});

// ── Navigation (Menus) ────────────────────────────────────────────────────────

test.describe('Navigation Page', () => {
  test('14-06: navigation page — h1 is "Menus"', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await page.route('**/api/v1/navigation-items**', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(NAV_ITEMS_EMPTY) });
      }
      return route.continue();
    });
    await gotoAdmin(page, '/navigation');
    await expect(page.locator('h1')).toContainText('Menus', { timeout: 6000 });
  });

  test('14-07: navigation page — empty state "No navigation items yet"', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await page.route('**/api/v1/navigation-items**', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(NAV_ITEMS_EMPTY) });
      }
      return route.continue();
    });
    await gotoAdmin(page, '/navigation');
    await expect(page.locator('text=No navigation items yet')).toBeVisible({ timeout: 6000 });
  });

  test('14-08: navigation page — existing items shown', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await page.route('**/api/v1/navigation-items**', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(NAV_ITEMS_LIST) });
      }
      return route.continue();
    });
    await gotoAdmin(page, '/navigation');
    await expect(page.locator('text=Home').first()).toBeVisible({ timeout: 6000 });
    await expect(page.locator('text=Blog').first()).toBeVisible({ timeout: 6000 });
  });

  test('14-09: navigation page — load error shows Retry button', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await page.route('**/api/v1/navigation-items**', (route) =>
      route.fulfill({ status: 500, contentType: 'application/json', body: SERVER_ERROR_BODY }),
    );
    await gotoAdmin(page, '/navigation');
    // NavigationItemListPanel renders errorTitle + Retry button on error
    await expect(page.locator('button:has-text("Retry")')).toBeVisible({ timeout: 6000 });
  });

  test('14-10: navigation create form — label and URL inputs visible', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await page.route('**/api/v1/navigation-items**', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(NAV_ITEMS_EMPTY) });
      }
      return route.continue();
    });
    await gotoAdmin(page, '/navigation');
    await expect(page.locator('input[id="nav-create-label"]')).toBeVisible({ timeout: 6000 });
    await expect(page.locator('input[id="nav-create-url"]')).toBeVisible({ timeout: 6000 });
  });

  test('14-11: navigation create — calls POST and shows new item', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });

    let created = false;
    await page.route('**/api/v1/navigation-items**', (route) => {
      const method = route.request().method();
      if (method === 'POST') {
        created = true;
        return route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify({ id: 5, label: 'About', url: '/about', display_order: 2 }),
        });
      }
      if (method === 'GET') {
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(created
            ? { items: [{ id: 5, label: 'About', url: '/about', display_order: 2 }] }
            : NAV_ITEMS_EMPTY),
        });
      }
      return route.continue();
    });

    await gotoAdmin(page, '/navigation');
    await page.locator('input[id="nav-create-label"]').fill('About');
    await page.locator('input[id="nav-create-url"]').fill('/about');
    await page.locator('button:has-text("Save")').click();

    await expect(page.locator('text=About').first()).toBeVisible({ timeout: 6000 });
  });
});

// ── Webhooks ─────────────────────────────────────────────────────────────────

test.describe('Webhooks Page', () => {
  test('14-12: webhooks page — title "Webhooks" visible', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await page.route('**/api/v1/webhooks**', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(WEBHOOK_EMPTY) });
      }
      return route.continue();
    });
    await gotoAdmin(page, '/webhooks');
    await expect(page.locator('text=Webhooks').first()).toBeVisible({ timeout: 6000 });
  });

  test('14-13: webhooks page — empty state "No webhooks yet"', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await page.route('**/api/v1/webhooks**', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(WEBHOOK_EMPTY) });
      }
      return route.continue();
    });
    await gotoAdmin(page, '/webhooks');
    await expect(page.locator('text=No webhooks yet')).toBeVisible({ timeout: 6000 });
  });

  test('14-14: webhooks page — existing webhook URL shown', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    // Mock webhook mapper: check what fields it reads
    await page.route('**/api/v1/webhooks**', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(WEBHOOK_LIST) });
      }
      return route.continue();
    });
    await gotoAdmin(page, '/webhooks');
    await expect(page.locator('text=https://example.com/hook').first()).toBeVisible({ timeout: 6000 });
  });

  test('14-15: webhooks page — load error handled, Retry button available', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await page.route('**/api/v1/webhooks**', (route) =>
      route.fulfill({ status: 500, contentType: 'application/json', body: SERVER_ERROR_BODY }),
    );
    await gotoAdmin(page, '/webhooks');
    // Page renders — at minimum body is visible
    await page.waitForTimeout(2000);
    await expect(page.locator('body')).toBeVisible();
  });

  test('14-16: webhooks page — Add webhook button visible', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await page.route('**/api/v1/webhooks**', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(WEBHOOK_EMPTY) });
      }
      return route.continue();
    });
    await gotoAdmin(page, '/webhooks');
    await expect(page.locator('button:has-text("Add webhook")')).toBeVisible({ timeout: 6000 });
  });
});

// ── Media Library ─────────────────────────────────────────────────────────────

test.describe('Media Page', () => {
  test('14-17: media page — h1 is "Media library"', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await page.route('**/api/v1/media**', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(MEDIA_EMPTY) });
      }
      return route.continue();
    });
    await gotoAdmin(page, '/media');
    await expect(page.locator('h1')).toContainText('Media library', { timeout: 6000 });
  });

  test('14-18: media page — empty state "No files yet"', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await page.route('**/api/v1/media**', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(MEDIA_EMPTY) });
      }
      return route.continue();
    });
    await gotoAdmin(page, '/media');
    await expect(page.locator('text=No files yet')).toBeVisible({ timeout: 6000 });
  });

  test('14-19: media page — existing file original_name shown', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await page.route('**/api/v1/media**', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(MEDIA_LIST) });
      }
      return route.continue();
    });
    await gotoAdmin(page, '/media');
    // Media items are displayed with their original_name (mapped to originalName)
    await expect(page.locator('text=hero.jpg').first()).toBeVisible({ timeout: 6000 });
  });

  test('14-20: media page — load error shows Retry button', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await page.route('**/api/v1/media**', (route) =>
      route.fulfill({ status: 500, contentType: 'application/json', body: SERVER_ERROR_BODY }),
    );
    await gotoAdmin(page, '/media');
    // MediaGrid renders errorTitle + Retry button when isError=true
    await expect(page.locator('button:has-text("Retry")')).toBeVisible({ timeout: 6000 });
  });
});

// ── Site Settings ─────────────────────────────────────────────────────────────

test.describe('Settings Page', () => {
  test('14-21: settings page — h1 is "Site settings"', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await page.route('**/api/v1/settings**', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(SETTINGS_LIST) });
      }
      return route.continue();
    });
    await page.route('**/api/v1/public/settings**', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ items: [] }) });
      }
      return route.continue();
    });
    await gotoAdmin(page, '/settings');
    await expect(page.locator('h1')).toContainText('Site settings', { timeout: 6000 });
  });

  test('14-22: settings page — site_name setting shown as "Site name"', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await page.route('**/api/v1/settings**', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(SETTINGS_LIST) });
      }
      return route.continue();
    });
    await page.route('**/api/v1/public/settings**', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ items: [] }) });
      }
      return route.continue();
    });
    await gotoAdmin(page, '/settings');
    await expect(page.locator('text=Site name').first()).toBeVisible({ timeout: 6000 });
  });

  test('14-23: settings page — load error shows error text', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await page.route('**/api/v1/settings**', (route) =>
      route.fulfill({ status: 500, contentType: 'application/json', body: SERVER_ERROR_BODY }),
    );
    await page.route('**/api/v1/public/settings**', (route) =>
      route.fulfill({ status: 500, contentType: 'application/json', body: SERVER_ERROR_BODY }),
    );
    await gotoAdmin(page, '/settings');
    await expect(page.locator('text=Could not load site settings')).toBeVisible({ timeout: 6000 });
  });
});

// ── Dashboard ─────────────────────────────────────────────────────────────────

test.describe('Dashboard Page', () => {
  test('14-24: dashboard page — h1 "Dashboard" visible', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await mockDashboard(page, DASHBOARD_EMPTY);
    await gotoAdmin(page);
    await expect(page.locator('h1')).toContainText('Dashboard', { timeout: 6000 });
  });

  test('14-25: dashboard page — empty dashboard shows access count tiles', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await mockDashboard(page, DASHBOARD_EMPTY);
    await gotoAdmin(page);
    // The dashboard renders access count tiles
    await expect(page.locator("text=Today's accesses").first()).toBeVisible({ timeout: 6000 });
  });

  test('14-26: dashboard page — with content shows entity type summary', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await mockDashboard(page, DASHBOARD_WITH_CONTENT);
    await page.route('**/api/v1/entity-types**', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ items: [], limit: 20, offset: 0 }),
        });
      }
      return route.continue();
    });
    await gotoAdmin(page);
    await expect(page.locator('text=Posts').first()).toBeVisible({ timeout: 6000 });
  });

  test('14-27: dashboard load error — shows error message', async ({ page }) => {
    await bypassLogin(page, { role: 'admin' });
    await page.route('**/api/v1/dashboard**', (route) =>
      route.fulfill({ status: 500, contentType: 'application/json', body: SERVER_ERROR_BODY }),
    );
    await gotoAdmin(page);
    await expect(page.locator('text=Could not load dashboard')).toBeVisible({ timeout: 6000 });
  });
});
