/**
 * Category: Full Scenarios — Personas & Cross-feature
 *
 * End-to-end scenarios modelling realistic multi-persona and cross-feature
 * workflows: admin/editor role combinations, superadmin org management,
 * error cascades, schema evolution, tenant admin setup, and comprehensive
 * site teardown scenarios.
 *
 * All API calls are intercepted — no real backend required.
 * Routes registered AFTER bypassLogin take LIFO priority over the
 * default entity-types handler registered inside bypassLogin.
 *
 * DTO shape note: ALL mock responses use snake_case to match the
 * backend API format that the frontend mappers expect.
 */

import { test, expect } from '@playwright/test';
import { bypassLogin, gotoAdmin, gotoSuperadmin, BASE_URL } from '../fixtures/helpers.js';
import {
  ADMIN_TOKEN,
  ENTITY_TYPE_LIST_EMPTY,
  ENTITY_TYPE_LIST,
  TAG_LIST_EMPTY,
  USER_LIST_EMPTY,
  ORGANIZATION_LIST,
  ORGANIZATION_LIST_EMPTY,
  TAG_CREATED,
} from '../fixtures/api-mocks.js';

// ── Shared builder helpers ────────────────────────────────────────────────────

function makeEntityType(id: number, name: string, slug: string) {
  return { id, name, slug, is_pinned: false, labels: null, permalink_pattern: null, previous_permalink_pattern: null };
}

function makeFieldDef(id: number, entityTypeId: number, fieldKey: string, dataType: string) {
  return { id, entity_type_id: entityTypeId, field_key: fieldKey, data_type: dataType, target_entity_type_id: null, cardinality: null };
}

function makeTag(id: number, name: string) {
  return { id, slug: name.toLowerCase().replace(/\s+/g, '-'), name };
}

function makeNavItem(id: number, label: string, url: string) {
  return { id, label, url, display_order: id, created_at: '2026-06-01T00:00:00Z', updated_at: '2026-06-01T00:00:00Z' };
}

// ── Tests ─────────────────────────────────────────────────────────────────────

test.describe('Full Scenarios — Personas & Cross-feature', () => {
  // ── 19-01: Admin and editor on same site — admin creates, editor views ──────

  test(
    '19-01: admin creates "Articles" + field; editor views entity-types (no form) and records page',
    async ({ page }) => {
      await bypassLogin(page);

      const entityTypes: ReturnType<typeof makeEntityType>[] = [];
      const fieldDefs: ReturnType<typeof makeFieldDef>[] = [];

      // LIFO: override entity-types endpoint registered inside bypassLogin
      await page.route('**/api/v1/entity-types**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          const body = route.request().postDataJSON() as { name: string; slug: string };
          const item = makeEntityType(entityTypes.length + 1, body.name, body.slug);
          entityTypes.push(item);
          return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
        }
        if (method === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...entityTypes], limit: 100, offset: 0 }),
          });
        }
        return route.continue();
      });

      await page.route('**/api/v1/field-defs**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          const body = route.request().postDataJSON() as { field_key: string; data_type: string };
          const item = makeFieldDef(fieldDefs.length + 1, 1, body.field_key, body.data_type);
          fieldDefs.push(item);
          return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
        }
        if (method === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...fieldDefs], limit: 20, offset: 0 }),
          });
        }
        return route.continue();
      });

      // Phase 1 (admin): create entity type "Articles"
      await gotoAdmin(page, '/entity-types');
      await page.locator('input[id="entity-type-name"]').fill('Articles');
      await page.locator('input[id="entity-type-slug"]').fill('articles');
      await page.locator('button:has-text("Create content type")').click();
      await expect(page.locator('text=Articles').first()).toBeVisible({ timeout: 6000 });

      // Navigate to fields page and add 1 field
      await page.locator('a[href*="/articles/fields"]').first().click();
      await expect(page).toHaveURL(/\/articles\/fields/, { timeout: 6000 });

      await page.locator('input[id="field-def-key"]').fill('title');
      await page.locator('select[id="field-def-data-type"]').selectOption('text');
      await page.locator('button:has-text("Add field")').click();
      await expect(page.locator('button:has-text("Edit")')).toHaveCount(1, { timeout: 6000 });

      // Phase 2 (editor): clear localStorage and re-inject as editor
      await page.evaluate(() => { localStorage.clear(); });
      await page.goto(BASE_URL);

      // Inject editor session
      await page.evaluate(
        ([k, v]) => localStorage.setItem(k, v),
        ['nene_records_token', JSON.stringify({ token: ADMIN_TOKEN, expiresAt: '2099-01-01T00:00:00Z', email: 'editor@example.com', role: 'editor' })] as [string, string],
      );

      // Re-register entity-types route (LIFO on new session)
      await page.route('**/api/v1/entity-types**', (route) => {
        if (route.request().method() === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...entityTypes], limit: 100, offset: 0 }),
          });
        }
        return route.continue();
      });

      // Entities endpoint for records page
      await page.route('**/api/v1/entities**', (route) => {
        if (route.request().method() === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [], limit: 20, offset: 0, total: 0 }),
          });
        }
        return route.continue();
      });

      // Editor sees entity-types page but NO create form
      await gotoAdmin(page, '/entity-types');
      await expect(page.locator('input[id="entity-type-name"]')).not.toBeVisible({ timeout: 6000 });

      // Records page for Articles still accessible
      await gotoAdmin(page, '/articles');
      await expect(page.locator('h1')).toContainText('Articles', { timeout: 6000 });
    },
  );

  // ── 19-02: Superadmin org audit — sees organizations + can access admin area ─

  test(
    '19-02: superadmin sees ORGANIZATION_LIST ("Acme Corp", "Globex Inc"), then navigates to admin dashboard',
    async ({ page }) => {
      await bypassLogin(page, { role: 'superadmin' });

      // Organizations endpoint
      await page.route('**/api/v1/organizations**', (route) => {
        if (route.request().method() === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(ORGANIZATION_LIST),
          });
        }
        return route.continue();
      });

      // Dashboard endpoint for admin area
      await page.route('**/api/v1/dashboard**', (route) => {
        if (route.request().method() === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ recent_published: [], today_access_count: 0, this_month_access_count: 0, entity_type_summary: [] }),
          });
        }
        return route.continue();
      });

      // Entity-types for admin area (sidebar)
      await page.route('**/api/v1/entity-types**', (route) => {
        if (route.request().method() === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(ENTITY_TYPE_LIST_EMPTY),
          });
        }
        return route.continue();
      });

      // Navigate to superadmin organizations list
      await gotoSuperadmin(page, '/organizations');
      await expect(page.locator('text=Acme Corp').first()).toBeVisible({ timeout: 6000 });
      await expect(page.locator('text=Globex Inc').first()).toBeVisible({ timeout: 6000 });

      // Navigate to admin area — dashboard accessible
      await gotoAdmin(page, '');
      await expect(page.locator('h1')).toContainText('Dashboard', { timeout: 6000 });
    },
  );

  // ── 19-03: Tech blog persona — code-focused schema (markdown + text + enum) ─

  test(
    '19-03: admin "Dev" creates "Code Tutorials" with 4 fields (title/code/language/level) → records "New item" visible',
    async ({ page }) => {
      await bypassLogin(page);

      const entityTypes: ReturnType<typeof makeEntityType>[] = [];
      const fieldDefs: ReturnType<typeof makeFieldDef>[] = [];

      await page.route('**/api/v1/entity-types**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          const body = route.request().postDataJSON() as { name: string; slug: string };
          const item = makeEntityType(entityTypes.length + 1, body.name, body.slug);
          entityTypes.push(item);
          return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
        }
        if (method === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...entityTypes], limit: 100, offset: 0 }),
          });
        }
        return route.continue();
      });

      await page.route('**/api/v1/field-defs**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          const body = route.request().postDataJSON() as { field_key: string; data_type: string };
          const item = makeFieldDef(fieldDefs.length + 1, 1, body.field_key, body.data_type);
          fieldDefs.push(item);
          return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
        }
        if (method === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...fieldDefs], limit: 20, offset: 0 }),
          });
        }
        return route.continue();
      });

      await page.route('**/api/v1/entities**', (route) => {
        if (route.request().method() === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [], limit: 20, offset: 0, total: 0 }),
          });
        }
        return route.continue();
      });

      // Create entity type
      await gotoAdmin(page, '/entity-types');
      await page.locator('input[id="entity-type-name"]').fill('Code Tutorials');
      await page.locator('input[id="entity-type-slug"]').fill('code-tutorials');
      await page.locator('button:has-text("Create content type")').click();
      await expect(page.locator('text=Code Tutorials').first()).toBeVisible({ timeout: 6000 });

      // Navigate to fields and add 4 fields
      await page.locator('a[href*="/code-tutorials/fields"]').first().click();
      await expect(page).toHaveURL(/\/code-tutorials\/fields/, { timeout: 6000 });

      const fields = [
        { key: 'title', type: 'text' },
        { key: 'code', type: 'markdown' },
        { key: 'language', type: 'enum' },
        { key: 'level', type: 'enum' },
      ];
      for (const field of fields) {
        await page.locator('input[id="field-def-key"]').fill(field.key);
        await page.locator('select[id="field-def-data-type"]').selectOption(field.type);
        await page.locator('button:has-text("Add field")').click();
        await expect(page.locator('input[id="field-def-key"]')).toHaveValue('', { timeout: 6000 });
      }

      // 4 Edit buttons
      await expect(page.locator('button:has-text("Edit")')).toHaveCount(4, { timeout: 6000 });

      // Records page — "New item" button visible
      await gotoAdmin(page, '/code-tutorials');
      await expect(page.locator('button:has-text("New item")')).toBeVisible({ timeout: 6000 });
    },
  );

  // ── 19-04: Marketing LP persona — rapid multi-LP creation ────────────────

  test(
    '19-04: admin "Marketer" creates 4 entity types sequentially — all 4 visible, 4 Edit buttons',
    async ({ page }) => {
      await bypassLogin(page);

      const entityTypes: ReturnType<typeof makeEntityType>[] = [];

      await page.route('**/api/v1/entity-types**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          const body = route.request().postDataJSON() as { name: string; slug: string };
          const item = makeEntityType(entityTypes.length + 1, body.name, body.slug);
          entityTypes.push(item);
          return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
        }
        if (method === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...entityTypes], limit: 100, offset: 0 }),
          });
        }
        return route.continue();
      });

      await gotoAdmin(page, '/entity-types');

      const campaigns = [
        { name: 'Campaign A', slug: 'campaign-a' },
        { name: 'Campaign B', slug: 'campaign-b' },
        { name: 'Campaign C', slug: 'campaign-c' },
        { name: 'Campaign D', slug: 'campaign-d' },
      ];
      for (const c of campaigns) {
        await page.locator('input[id="entity-type-name"]').fill(c.name);
        await page.locator('input[id="entity-type-slug"]').fill(c.slug);
        await page.locator('button:has-text("Create content type")').click();
        await expect(page.locator('input[id="entity-type-name"]')).toHaveValue('', { timeout: 6000 });
      }

      for (const c of campaigns) {
        await expect(page.locator(`text=${c.name}`).first()).toBeVisible({ timeout: 6000 });
      }
      await expect(page.locator('button:has-text("Edit")')).toHaveCount(4, { timeout: 6000 });
    },
  );

  // ── 19-05: Full content workflow — schema → tags → records → navigation ─────

  test(
    '19-05: admin "Full-stack" creates Articles type + title field + tag + nav item',
    async ({ page }) => {
      await bypassLogin(page);

      const entityTypes: ReturnType<typeof makeEntityType>[] = [];
      const fieldDefs: ReturnType<typeof makeFieldDef>[] = [];
      const tags: ReturnType<typeof makeTag>[] = [];
      const navItems: ReturnType<typeof makeNavItem>[] = [];

      await page.route('**/api/v1/entity-types**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          const body = route.request().postDataJSON() as { name: string; slug: string };
          const item = makeEntityType(entityTypes.length + 1, body.name, body.slug);
          entityTypes.push(item);
          return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
        }
        if (method === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...entityTypes], limit: 100, offset: 0 }),
          });
        }
        return route.continue();
      });

      await page.route('**/api/v1/field-defs**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          const body = route.request().postDataJSON() as { field_key: string; data_type: string };
          const item = makeFieldDef(fieldDefs.length + 1, 1, body.field_key, body.data_type);
          fieldDefs.push(item);
          return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
        }
        if (method === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...fieldDefs], limit: 20, offset: 0 }),
          });
        }
        return route.continue();
      });

      await page.route('**/api/v1/tags**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          const body = route.request().postDataJSON() as { name: string; slug: string };
          const item = makeTag(tags.length + 1, body.name);
          tags.push(item);
          return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
        }
        if (method === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...tags], limit: 20, offset: 0 }),
          });
        }
        return route.continue();
      });

      await page.route('**/api/v1/navigation-items**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          const body = route.request().postDataJSON() as { label: string; url: string };
          const item = makeNavItem(navItems.length + 1, body.label, body.url);
          navItems.push(item);
          return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
        }
        if (method === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...navItems] }),
          });
        }
        return route.continue();
      });

      await page.route('**/api/v1/entities**', (route) => {
        if (route.request().method() === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [], limit: 20, offset: 0, total: 0 }),
          });
        }
        return route.continue();
      });

      // Step 1: Create entity type "Articles"
      await gotoAdmin(page, '/entity-types');
      await page.locator('input[id="entity-type-name"]').fill('Articles');
      await page.locator('input[id="entity-type-slug"]').fill('articles');
      await page.locator('button:has-text("Create content type")').click();
      await expect(page.locator('text=Articles').first()).toBeVisible({ timeout: 6000 });

      // Step 2: Add field "title" (text)
      await page.locator('a[href*="/articles/fields"]').first().click();
      await expect(page).toHaveURL(/\/articles\/fields/, { timeout: 6000 });
      await page.locator('input[id="field-def-key"]').fill('title');
      await page.locator('select[id="field-def-data-type"]').selectOption('text');
      await page.locator('button:has-text("Add field")').click();
      await expect(page.locator('button:has-text("Edit")')).toHaveCount(1, { timeout: 6000 });

      // Step 3: Create tag "featured"
      await gotoAdmin(page, '/tags');
      await page.locator('input[id="tag-name"]').fill('featured');
      await page.locator('input[id="tag-slug"]').fill('featured');
      await page.locator('button:has-text("Create tag")').click();
      await expect(page.locator('text=featured').first()).toBeVisible({ timeout: 6000 });

      // Step 4: Navigate to /admin/articles (records page)
      await gotoAdmin(page, '/articles');
      await expect(page.locator('h1')).toContainText('Articles', { timeout: 6000 });

      // Step 5: Navigate to navigation → add nav item "Blog" → /blog
      await gotoAdmin(page, '/navigation');
      await page.locator('input[id="nav-create-label"]').fill('Blog');
      await page.locator('input[id="nav-create-url"]').fill('/blog');
      await page.locator('button:has-text("Save")').click();
      await expect(page.locator('text=Blog').first()).toBeVisible({ timeout: 6000 });

      // Verify: articles type, 1 field, tag, nav item all set up
      expect(entityTypes.length).toBe(1);
      expect(fieldDefs.length).toBe(1);
      expect(tags.length).toBe(1);
      expect(navItems.length).toBe(1);
    },
  );

  // ── 19-06: Error cascade recovery — entity type + field errors, both recovered

  test(
    '19-06: entity type POST fails first, then succeeds; field POST fails first, then succeeds',
    async ({ page }) => {
      await bypassLogin(page);

      let etPostCount = 0;
      let fieldPostCount = 0;
      const entityTypes: ReturnType<typeof makeEntityType>[] = [];
      const fieldDefs: ReturnType<typeof makeFieldDef>[] = [];

      await page.route('**/api/v1/entity-types**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          etPostCount++;
          if (etPostCount === 1) {
            return route.fulfill({
              status: 500,
              contentType: 'application/json',
              body: JSON.stringify({ type: 'about:blank', title: 'Internal Server Error', status: 500, detail: 'Temporary failure', instance: '/api/v1/entity-types' }),
            });
          }
          const body = route.request().postDataJSON() as { name: string; slug: string };
          const item = makeEntityType(entityTypes.length + 1, body.name, body.slug);
          entityTypes.push(item);
          return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
        }
        if (method === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...entityTypes], limit: 100, offset: 0 }),
          });
        }
        return route.continue();
      });

      await page.route('**/api/v1/field-defs**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          fieldPostCount++;
          if (fieldPostCount === 1) {
            return route.fulfill({
              status: 500,
              contentType: 'application/json',
              body: JSON.stringify({ type: 'about:blank', title: 'Internal Server Error', status: 500, detail: 'Temporary failure', instance: '/api/v1/field-defs' }),
            });
          }
          const body = route.request().postDataJSON() as { field_key: string; data_type: string };
          const item = makeFieldDef(fieldDefs.length + 1, 1, body.field_key, body.data_type);
          fieldDefs.push(item);
          return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
        }
        if (method === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...fieldDefs], limit: 20, offset: 0 }),
          });
        }
        return route.continue();
      });

      // First entity type POST → fails
      await gotoAdmin(page, '/entity-types');
      await page.locator('input[id="entity-type-name"]').fill('Recovery Site');
      await page.locator('input[id="entity-type-slug"]').fill('recovery-site');
      await page.locator('button:has-text("Create content type")').click();
      await page.waitForTimeout(500);

      // Form still accessible after failure
      await expect(page.locator('input[id="entity-type-name"]')).toBeVisible({ timeout: 3000 });

      // Second entity type POST → succeeds
      await page.locator('input[id="entity-type-name"]').fill('Recovery Site');
      await page.locator('input[id="entity-type-slug"]').fill('recovery-site');
      await page.locator('button:has-text("Create content type")').click();
      await expect(page.locator('text=Recovery Site').first()).toBeVisible({ timeout: 6000 });

      // Navigate to fields page
      await page.locator('a[href*="/recovery-site/fields"]').first().click();
      await expect(page).toHaveURL(/\/recovery-site\/fields/, { timeout: 6000 });

      // First field POST → fails
      await page.locator('input[id="field-def-key"]').fill('content');
      await page.locator('select[id="field-def-data-type"]').selectOption('text');
      await page.locator('button:has-text("Add field")').click();
      await page.waitForTimeout(500);

      // Form still accessible after failure
      await expect(page.locator('input[id="field-def-key"]')).toBeVisible({ timeout: 3000 });

      // Second field POST → succeeds
      await page.locator('input[id="field-def-key"]').fill('content');
      await page.locator('select[id="field-def-data-type"]').selectOption('text');
      await page.locator('button:has-text("Add field")').click();
      await expect(page.locator('input[id="field-def-key"]')).toHaveValue('', { timeout: 6000 });

      // Verify: 1 entity type + 1 field created
      expect(entityTypes.length).toBe(1);
      expect(fieldDefs.length).toBe(1);
    },
  );

  // ── 19-07: Schema evolution — add 3 fields then delete 1 ──────────────────

  test(
    '19-07: create "Blog" + 3 fields (title/body/published_at) → 3 Edit buttons → delete "body" → 2 Edit buttons remain',
    async ({ page }) => {
      await bypassLogin(page);

      const entityTypes: ReturnType<typeof makeEntityType>[] = [];
      const fieldDefs: ReturnType<typeof makeFieldDef>[] = [];

      await page.route('**/api/v1/entity-types**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          const body = route.request().postDataJSON() as { name: string; slug: string };
          const item = makeEntityType(entityTypes.length + 1, body.name, body.slug);
          entityTypes.push(item);
          return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
        }
        if (method === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...entityTypes], limit: 100, offset: 0 }),
          });
        }
        return route.continue();
      });

      await page.route('**/api/v1/field-defs**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          const body = route.request().postDataJSON() as { field_key: string; data_type: string };
          const item = makeFieldDef(fieldDefs.length + 1, 1, body.field_key, body.data_type);
          fieldDefs.push(item);
          return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
        }
        if (method === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...fieldDefs], limit: 20, offset: 0 }),
          });
        }
        return route.continue();
      });

      // DELETE field (path-based: /field-defs/:id)
      await page.route('**/api/v1/field-defs/**', (route) => {
        if (route.request().method() === 'DELETE') {
          // Remove the "body" field (index 1)
          const idx = fieldDefs.findIndex((f) => f.field_key === 'body');
          if (idx !== -1) fieldDefs.splice(idx, 1);
          return route.fulfill({ status: 204, body: '' });
        }
        return route.continue();
      });

      // Create entity type "Blog"
      await gotoAdmin(page, '/entity-types');
      await page.locator('input[id="entity-type-name"]').fill('Blog');
      await page.locator('input[id="entity-type-slug"]').fill('blog');
      await page.locator('button:has-text("Create content type")').click();
      await expect(page.locator('text=Blog').first()).toBeVisible({ timeout: 6000 });

      // Navigate to fields and add 3 fields
      await page.locator('a[href*="/blog/fields"]').first().click();
      await expect(page).toHaveURL(/\/blog\/fields/, { timeout: 6000 });

      const fields = [
        { key: 'title', type: 'text' },
        { key: 'body', type: 'markdown' },
        { key: 'published_at', type: 'datetime' },
      ];
      for (const field of fields) {
        await page.locator('input[id="field-def-key"]').fill(field.key);
        await page.locator('select[id="field-def-data-type"]').selectOption(field.type);
        await page.locator('button:has-text("Add field")').click();
        await expect(page.locator('input[id="field-def-key"]')).toHaveValue('', { timeout: 6000 });
      }

      // 3 Edit buttons
      await expect(page.locator('button:has-text("Edit")')).toHaveCount(3, { timeout: 6000 });

      // Delete "body" field — click the Delete button for the "body" row
      // Find the row containing "body" and click its Delete button
      await page.locator('button:has-text("Delete")').nth(1).click();

      // Confirm dialog
      await expect(page.locator('text=Delete field?')).toBeVisible({ timeout: 3000 });
      await page.locator('[role="dialog"] button:has-text("Delete")').click();

      // 2 Edit buttons remain
      await expect(page.locator('button:has-text("Edit")')).toHaveCount(2, { timeout: 6000 });
    },
  );

  // ── 19-08: Org-scoped admin (tenant admin) sets up blog ────────────────────

  test(
    '19-08: org-scoped admin (orgId: 1) creates "Tenant Blog" entity type + "title" field — both created',
    async ({ page }) => {
      await bypassLogin(page, { role: 'admin', email: 'admin@tenant.com', orgId: 1 });

      const entityTypes: ReturnType<typeof makeEntityType>[] = [];
      const fieldDefs: ReturnType<typeof makeFieldDef>[] = [];

      await page.route('**/api/v1/entity-types**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          const body = route.request().postDataJSON() as { name: string; slug: string };
          const item = makeEntityType(entityTypes.length + 1, body.name, body.slug);
          entityTypes.push(item);
          return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
        }
        if (method === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...entityTypes], limit: 100, offset: 0 }),
          });
        }
        return route.continue();
      });

      await page.route('**/api/v1/field-defs**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          const body = route.request().postDataJSON() as { field_key: string; data_type: string };
          const item = makeFieldDef(fieldDefs.length + 1, 1, body.field_key, body.data_type);
          fieldDefs.push(item);
          return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
        }
        if (method === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...fieldDefs], limit: 20, offset: 0 }),
          });
        }
        return route.continue();
      });

      // Create entity type "Tenant Blog"
      await gotoAdmin(page, '/entity-types');
      await page.locator('input[id="entity-type-name"]').fill('Tenant Blog');
      await page.locator('input[id="entity-type-slug"]').fill('tenant-blog');
      await page.locator('button:has-text("Create content type")').click();
      await expect(page.locator('text=Tenant Blog').first()).toBeVisible({ timeout: 6000 });

      // Navigate to fields
      await page.locator('a[href*="/tenant-blog/fields"]').first().click();
      await expect(page).toHaveURL(/\/tenant-blog\/fields/, { timeout: 6000 });

      // Add field "title"
      await page.locator('input[id="field-def-key"]').fill('title');
      await page.locator('select[id="field-def-data-type"]').selectOption('text');
      await page.locator('button:has-text("Add field")').click();
      await expect(page.locator('button:has-text("Edit")')).toHaveCount(1, { timeout: 6000 });

      expect(entityTypes.length).toBe(1);
      expect(fieldDefs.length).toBe(1);
    },
  );

  // ── 19-09: Blog site + LP site — dual-persona setup with role switch ────────

  test(
    '19-09: admin creates "Blog Posts" + "Landing Pages"; editor after role switch sees both types, no create form',
    async ({ page }) => {
      await bypassLogin(page);

      const entityTypes: ReturnType<typeof makeEntityType>[] = [];

      // LIFO: override entity-types handler
      await page.route('**/api/v1/entity-types**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          const body = route.request().postDataJSON() as { name: string; slug: string };
          const item = makeEntityType(entityTypes.length + 1, body.name, body.slug);
          entityTypes.push(item);
          return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
        }
        if (method === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...entityTypes], limit: 100, offset: 0 }),
          });
        }
        return route.continue();
      });

      // Phase 1 (admin): Create "Blog Posts"
      await gotoAdmin(page, '/entity-types');
      await page.locator('input[id="entity-type-name"]').fill('Blog Posts');
      await page.locator('input[id="entity-type-slug"]').fill('blog-posts');
      await page.locator('button:has-text("Create content type")').click();
      await expect(page.locator('text=Blog Posts').first()).toBeVisible({ timeout: 6000 });

      // Phase 2 (same admin): Create "Landing Pages"
      await page.locator('input[id="entity-type-name"]').fill('Landing Pages');
      await page.locator('input[id="entity-type-slug"]').fill('landing-pages');
      await page.locator('button:has-text("Create content type")').click();
      await expect(page.locator('text=Landing Pages').first()).toBeVisible({ timeout: 6000 });
      expect(entityTypes.length).toBe(2);

      // Phase 3 (editor): clear localStorage + inject editor session
      await page.evaluate(() => { localStorage.clear(); });
      await page.goto(BASE_URL);
      await page.evaluate(
        ([k, v]) => localStorage.setItem(k, v),
        ['nene_records_token', JSON.stringify({ token: ADMIN_TOKEN, expiresAt: '2099-01-01T00:00:00Z', email: 'editor@example.com', role: 'editor' })] as [string, string],
      );

      // Re-register entity-types route returning both types (LIFO)
      await page.route('**/api/v1/entity-types**', (route) => {
        if (route.request().method() === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...entityTypes], limit: 100, offset: 0 }),
          });
        }
        return route.continue();
      });

      // Editor: no create form on entity-types page
      await gotoAdmin(page, '/entity-types');
      await expect(page.locator('input[id="entity-type-name"]')).not.toBeVisible({ timeout: 6000 });

      // Both types visible for editor
      await expect(page.locator('text=Blog Posts').first()).toBeVisible({ timeout: 6000 });
      await expect(page.locator('text=Landing Pages').first()).toBeVisible({ timeout: 6000 });
    },
  );

  // ── 19-10: Recipe site persona — food blog with all field types ─────────────

  test(
    '19-10: admin "Chef" creates "Recipes" with 5 fields (title/instructions/prep_time/is_vegetarian/photo) — 5 Edit buttons',
    async ({ page }) => {
      await bypassLogin(page);

      const entityTypes: ReturnType<typeof makeEntityType>[] = [];
      const fieldDefs: ReturnType<typeof makeFieldDef>[] = [];

      await page.route('**/api/v1/entity-types**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          const body = route.request().postDataJSON() as { name: string; slug: string };
          const item = makeEntityType(entityTypes.length + 1, body.name, body.slug);
          entityTypes.push(item);
          return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
        }
        if (method === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...entityTypes], limit: 100, offset: 0 }),
          });
        }
        return route.continue();
      });

      await page.route('**/api/v1/field-defs**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          const body = route.request().postDataJSON() as { field_key: string; data_type: string };
          const item = makeFieldDef(fieldDefs.length + 1, 1, body.field_key, body.data_type);
          fieldDefs.push(item);
          return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
        }
        if (method === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...fieldDefs], limit: 20, offset: 0 }),
          });
        }
        return route.continue();
      });

      // Create entity type "Recipes"
      await gotoAdmin(page, '/entity-types');
      await page.locator('input[id="entity-type-name"]').fill('Recipes');
      await page.locator('input[id="entity-type-slug"]').fill('recipes');
      await page.locator('button:has-text("Create content type")').click();
      await expect(page.locator('text=Recipes').first()).toBeVisible({ timeout: 6000 });

      // Navigate to fields
      await page.locator('a[href*="/recipes/fields"]').first().click();
      await expect(page).toHaveURL(/\/recipes\/fields/, { timeout: 6000 });

      // Add 5 fields covering multiple types
      const fields = [
        { key: 'title', type: 'text' },
        { key: 'instructions', type: 'markdown' },
        { key: 'prep_time', type: 'int' },
        { key: 'is_vegetarian', type: 'bool' },
        { key: 'photo', type: 'image' },
      ];
      for (const field of fields) {
        await page.locator('input[id="field-def-key"]').fill(field.key);
        await page.locator('select[id="field-def-data-type"]').selectOption(field.type);
        await page.locator('button:has-text("Add field")').click();
        await expect(page.locator('input[id="field-def-key"]')).toHaveValue('', { timeout: 6000 });
      }

      await expect(page.locator('button:has-text("Edit")')).toHaveCount(5, { timeout: 6000 });
    },
  );

  // ── 19-11: Performance: 5 entity types + 2 fields for TypeA ────────────────

  test(
    '19-11: admin "Rapid" creates TypeA–TypeE (5 total); adds 2 fields to TypeA — TypeA has 2 fields, 5 Edit buttons on entity-types page',
    async ({ page }) => {
      await bypassLogin(page);

      const entityTypes: ReturnType<typeof makeEntityType>[] = [];
      const fieldDefs: ReturnType<typeof makeFieldDef>[] = [];

      await page.route('**/api/v1/entity-types**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          const body = route.request().postDataJSON() as { name: string; slug: string };
          const item = makeEntityType(entityTypes.length + 1, body.name, body.slug);
          entityTypes.push(item);
          return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
        }
        if (method === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...entityTypes], limit: 100, offset: 0 }),
          });
        }
        return route.continue();
      });

      await page.route('**/api/v1/field-defs**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          const body = route.request().postDataJSON() as { field_key: string; data_type: string };
          const item = makeFieldDef(fieldDefs.length + 1, 1, body.field_key, body.data_type);
          fieldDefs.push(item);
          return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
        }
        if (method === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...fieldDefs], limit: 20, offset: 0 }),
          });
        }
        return route.continue();
      });

      await gotoAdmin(page, '/entity-types');

      // Create 5 entity types
      const types = [
        { name: 'TypeA', slug: 'typea' },
        { name: 'TypeB', slug: 'typeb' },
        { name: 'TypeC', slug: 'typec' },
        { name: 'TypeD', slug: 'typed' },
        { name: 'TypeE', slug: 'typee' },
      ];
      for (const t of types) {
        await page.locator('input[id="entity-type-name"]').fill(t.name);
        await page.locator('input[id="entity-type-slug"]').fill(t.slug);
        await page.locator('button:has-text("Create content type")').click();
        await expect(page.locator('input[id="entity-type-name"]')).toHaveValue('', { timeout: 6000 });
      }

      // 5 Edit buttons on entity types page
      await expect(page.locator('button:has-text("Edit")')).toHaveCount(5, { timeout: 6000 });
      await expect(page.locator('text=TypeE').first()).toBeVisible({ timeout: 6000 });

      // Navigate to TypeA fields and add 2 fields
      await gotoAdmin(page, '/entity-types/typea/fields');
      await expect(page).toHaveURL(/\/typea\/fields/, { timeout: 6000 });

      await page.locator('input[id="field-def-key"]').fill('field1');
      await page.locator('select[id="field-def-data-type"]').selectOption('text');
      await page.locator('button:has-text("Add field")').click();
      await expect(page.locator('input[id="field-def-key"]')).toHaveValue('', { timeout: 6000 });

      await page.locator('input[id="field-def-key"]').fill('field2');
      await page.locator('select[id="field-def-data-type"]').selectOption('markdown');
      await page.locator('button:has-text("Add field")').click();
      await expect(page.locator('input[id="field-def-key"]')).toHaveValue('', { timeout: 6000 });

      // TypeA has 2 Edit buttons (2 fields)
      await expect(page.locator('button:has-text("Edit")')).toHaveCount(2, { timeout: 6000 });
      expect(fieldDefs.length).toBe(2);
    },
  );

  // ── 19-12: Abandoned workflow restart — partial setup cleared and restarted ──

  test(
    '19-12: fill entity type form without submitting; reload (gotoAdmin again); create "Final Name" → succeeds',
    async ({ page }) => {
      await bypassLogin(page);

      const entityTypes: ReturnType<typeof makeEntityType>[] = [];

      await page.route('**/api/v1/entity-types**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          const body = route.request().postDataJSON() as { name: string; slug: string };
          const item = makeEntityType(entityTypes.length + 1, body.name, body.slug);
          entityTypes.push(item);
          return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
        }
        if (method === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...entityTypes], limit: 100, offset: 0 }),
          });
        }
        return route.continue();
      });

      // Step 1: Fill form without submitting
      await gotoAdmin(page, '/entity-types');
      await page.locator('input[id="entity-type-name"]').fill('Draft Name');
      await page.locator('input[id="entity-type-slug"]').fill('draft-slug');
      // Do NOT click "Create content type" — simulate abandoned workflow

      // Step 2: "Reload" by navigating fresh (form state reset from React re-mount)
      await gotoAdmin(page, '/entity-types');

      // Form is blank (fresh mount)
      await expect(page.locator('input[id="entity-type-name"]')).toHaveValue('', { timeout: 6000 });

      // Step 3: Create "Final Name" successfully
      await page.locator('input[id="entity-type-name"]').fill('Final Name');
      await page.locator('input[id="entity-type-slug"]').fill('final-name');
      await page.locator('button:has-text("Create content type")').click();
      await expect(page.locator('text=Final Name').first()).toBeVisible({ timeout: 6000 });

      // "Draft Name" was never submitted — not in list
      expect(entityTypes.length).toBe(1);
      expect(entityTypes[0]?.name).toBe('Final Name');
    },
  );

  // ── 19-13: Multi-resource site — entity type + tag + nav + settings check ───

  test(
    '19-13: admin "Complete" creates "Pages" type + "homepage" tag + "Home" nav item; settings accessible; Pages persists',
    async ({ page }) => {
      await bypassLogin(page);

      const entityTypes: ReturnType<typeof makeEntityType>[] = [];
      const tags: ReturnType<typeof makeTag>[] = [];
      const navItems: ReturnType<typeof makeNavItem>[] = [];

      await page.route('**/api/v1/entity-types**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          const body = route.request().postDataJSON() as { name: string; slug: string };
          const item = makeEntityType(entityTypes.length + 1, body.name, body.slug);
          entityTypes.push(item);
          return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
        }
        if (method === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...entityTypes], limit: 100, offset: 0 }),
          });
        }
        return route.continue();
      });

      await page.route('**/api/v1/tags**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          const body = route.request().postDataJSON() as { name: string; slug: string };
          const item = makeTag(tags.length + 1, body.name);
          tags.push(item);
          return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
        }
        if (method === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...tags], limit: 20, offset: 0 }),
          });
        }
        return route.continue();
      });

      await page.route('**/api/v1/navigation-items**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          const body = route.request().postDataJSON() as { label: string; url: string };
          const item = makeNavItem(navItems.length + 1, body.label, body.url);
          navItems.push(item);
          return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
        }
        if (method === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...navItems] }),
          });
        }
        return route.continue();
      });

      await page.route('**/api/v1/settings**', (route) => {
        if (route.request().method() === 'GET') {
          return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ items: [] }) });
        }
        return route.continue();
      });

      await page.route('**/api/v1/public/settings**', (route) => {
        if (route.request().method() === 'GET') {
          return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ items: [] }) });
        }
        return route.continue();
      });

      // Step 1: Create entity type "Pages"
      await gotoAdmin(page, '/entity-types');
      await page.locator('input[id="entity-type-name"]').fill('Pages');
      await page.locator('input[id="entity-type-slug"]').fill('pages');
      await page.locator('button:has-text("Create content type")').click();
      await expect(page.locator('text=Pages').first()).toBeVisible({ timeout: 6000 });

      // Step 2: Create tag "homepage"
      await gotoAdmin(page, '/tags');
      await page.locator('input[id="tag-name"]').fill('homepage');
      await page.locator('input[id="tag-slug"]').fill('homepage');
      await page.locator('button:has-text("Create tag")').click();
      await expect(page.locator('text=homepage').first()).toBeVisible({ timeout: 6000 });

      // Step 3: Add nav item "Home" → /
      await gotoAdmin(page, '/navigation');
      await page.locator('input[id="nav-create-label"]').fill('Home');
      await page.locator('input[id="nav-create-url"]').fill('/');
      await page.locator('button:has-text("Save")').click();
      await expect(page.locator('text=Home').first()).toBeVisible({ timeout: 6000 });

      // Step 4: Navigate to settings — accessible
      await gotoAdmin(page, '/settings');
      await expect(page.locator('h1')).toContainText('Site settings', { timeout: 6000 });

      // Step 5: Navigate back to entity-types — "Pages" still visible
      await gotoAdmin(page, '/entity-types');
      await expect(page.locator('text=Pages').first()).toBeVisible({ timeout: 6000 });
    },
  );

  // ── 19-14: Long conversation analogue — 5 entity types × 1 field each ───────

  test(
    '19-14: admin "Builder" creates Blog/News/Events/Portfolio/Products (5 types); navigates to blog fields + adds "title" → 1 Edit button',
    async ({ page }) => {
      await bypassLogin(page);

      const entityTypes: ReturnType<typeof makeEntityType>[] = [];
      const fieldDefs: ReturnType<typeof makeFieldDef>[] = [];

      await page.route('**/api/v1/entity-types**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          const body = route.request().postDataJSON() as { name: string; slug: string };
          const item = makeEntityType(entityTypes.length + 1, body.name, body.slug);
          entityTypes.push(item);
          return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
        }
        if (method === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...entityTypes], limit: 100, offset: 0 }),
          });
        }
        return route.continue();
      });

      await page.route('**/api/v1/field-defs**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          const body = route.request().postDataJSON() as { field_key: string; data_type: string };
          const item = makeFieldDef(fieldDefs.length + 1, 1, body.field_key, body.data_type);
          fieldDefs.push(item);
          return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
        }
        if (method === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...fieldDefs], limit: 20, offset: 0 }),
          });
        }
        return route.continue();
      });

      await gotoAdmin(page, '/entity-types');

      // Create 5 entity types
      const allTypes = [
        { name: 'Blog', slug: 'blog' },
        { name: 'News', slug: 'news' },
        { name: 'Events', slug: 'events' },
        { name: 'Portfolio', slug: 'portfolio' },
        { name: 'Products', slug: 'products' },
      ];
      for (const t of allTypes) {
        await page.locator('input[id="entity-type-name"]').fill(t.name);
        await page.locator('input[id="entity-type-slug"]').fill(t.slug);
        await page.locator('button:has-text("Create content type")').click();
        await expect(page.locator('input[id="entity-type-name"]')).toHaveValue('', { timeout: 6000 });
      }

      // All 5 visible, 5 Edit buttons
      await expect(page.locator('button:has-text("Edit")')).toHaveCount(5, { timeout: 6000 });

      // Navigate to "blog" fields and add "title" field
      await gotoAdmin(page, '/entity-types/blog/fields');
      await expect(page).toHaveURL(/\/blog\/fields/, { timeout: 6000 });

      await page.locator('input[id="field-def-key"]').fill('title');
      await page.locator('select[id="field-def-data-type"]').selectOption('text');
      await page.locator('button:has-text("Add field")').click();
      await expect(page.locator('input[id="field-def-key"]')).toHaveValue('', { timeout: 6000 });

      // 1 Edit button on fields page
      await expect(page.locator('button:has-text("Edit")')).toHaveCount(1, { timeout: 6000 });
      expect(entityTypes.length).toBe(5);
    },
  );

  // ── 19-15: Full site teardown — delete all entity types sequentially ─────────

  test(
    '19-15: 3 entity types (Blog/News/Events) → delete each → empty state "No content types yet"',
    async ({ page }) => {
      const blogType = makeEntityType(1, 'Blog', 'blog');
      const newsType = makeEntityType(2, 'News', 'news');
      const eventsType = makeEntityType(3, 'Events', 'events');

      const remaining = [blogType, newsType, eventsType];

      await bypassLogin(page, {
        entityTypes: { items: [...remaining], limit: 100, offset: 0 },
      });

      let deleteCount = 0;

      // LIFO: override entity-types GET
      await page.route('**/api/v1/entity-types**', (route) => {
        if (route.request().method() === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...remaining], limit: 100, offset: 0 }),
          });
        }
        return route.continue();
      });

      // DELETE endpoint — removes the first item from remaining on each DELETE
      await page.route('**/api/v1/entity-types/**', (route) => {
        if (route.request().method() === 'DELETE') {
          deleteCount++;
          remaining.shift();
          return route.fulfill({ status: 204, body: '' });
        }
        return route.continue();
      });

      await gotoAdmin(page, '/entity-types');
      await expect(page.locator('text=Blog').first()).toBeVisible({ timeout: 6000 });
      await expect(page.locator('text=News').first()).toBeVisible({ timeout: 6000 });
      await expect(page.locator('text=Events').first()).toBeVisible({ timeout: 6000 });

      // Delete Blog (first item)
      await page.locator('button:has-text("Delete")').first().click();
      await expect(page.locator('text=Delete content type?')).toBeVisible({ timeout: 3000 });
      await page.locator('[role="dialog"] button:has-text("Delete")').click();
      await expect(page.locator('button:has-text("Edit")')).toHaveCount(2, { timeout: 6000 });

      // Delete News (now first)
      await page.locator('button:has-text("Delete")').first().click();
      await expect(page.locator('text=Delete content type?')).toBeVisible({ timeout: 3000 });
      await page.locator('[role="dialog"] button:has-text("Delete")').click();
      await expect(page.locator('button:has-text("Edit")')).toHaveCount(1, { timeout: 6000 });

      // Delete Events (last remaining)
      await page.locator('button:has-text("Delete")').first().click();
      await expect(page.locator('text=Delete content type?')).toBeVisible({ timeout: 3000 });
      await page.locator('[role="dialog"] button:has-text("Delete")').click();

      // Empty state
      await expect(page.locator('text=No content types yet')).toBeVisible({ timeout: 6000 });
      expect(deleteCount).toBe(3);
      expect(remaining.length).toBe(0);
    },
  );

  // ── 19-16: Comprehensive persona test — superadmin then org admin LP site ────

  test(
    '19-16: superadmin views org list; org admin creates "Landing Page" + "hero_title" field + navigates to records',
    async ({ page }) => {
      // Phase 1: superadmin sees organizations
      await bypassLogin(page, { role: 'superadmin' });

      await page.route('**/api/v1/organizations**', (route) => {
        if (route.request().method() === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(ORGANIZATION_LIST),
          });
        }
        return route.continue();
      });

      await gotoSuperadmin(page, '/organizations');
      await expect(page.locator('text=Acme Corp').first()).toBeVisible({ timeout: 6000 });
      await expect(page.locator('text=Globex Inc').first()).toBeVisible({ timeout: 6000 });

      // Phase 2: switch to org admin
      await page.evaluate(() => { localStorage.clear(); });
      await page.goto(BASE_URL);
      await page.evaluate(
        ([k, v]) => localStorage.setItem(k, v),
        ['nene_records_token', JSON.stringify({ token: ADMIN_TOKEN, expiresAt: '2099-01-01T00:00:00Z', email: 'admin@org.com', role: 'admin', orgId: 1 })] as [string, string],
      );

      const entityTypes: ReturnType<typeof makeEntityType>[] = [];
      const fieldDefs: ReturnType<typeof makeFieldDef>[] = [];

      // Register entity-types after session injection (LIFO)
      await page.route('**/api/v1/entity-types**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          const body = route.request().postDataJSON() as { name: string; slug: string };
          const item = makeEntityType(entityTypes.length + 1, body.name, body.slug);
          entityTypes.push(item);
          return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
        }
        if (method === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...entityTypes], limit: 100, offset: 0 }),
          });
        }
        return route.continue();
      });

      await page.route('**/api/v1/field-defs**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          const body = route.request().postDataJSON() as { field_key: string; data_type: string };
          const item = makeFieldDef(fieldDefs.length + 1, 1, body.field_key, body.data_type);
          fieldDefs.push(item);
          return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
        }
        if (method === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...fieldDefs], limit: 20, offset: 0 }),
          });
        }
        return route.continue();
      });

      await page.route('**/api/v1/entities**', (route) => {
        if (route.request().method() === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [], limit: 20, offset: 0, total: 0 }),
          });
        }
        return route.continue();
      });

      // Create entity type "Landing Page"
      await gotoAdmin(page, '/entity-types');
      await page.locator('input[id="entity-type-name"]').fill('Landing Page');
      await page.locator('input[id="entity-type-slug"]').fill('landing-page');
      await page.locator('button:has-text("Create content type")').click();
      await expect(page.locator('text=Landing Page').first()).toBeVisible({ timeout: 6000 });

      // Add field "hero_title" (text)
      await page.locator('a[href*="/landing-page/fields"]').first().click();
      await expect(page).toHaveURL(/\/landing-page\/fields/, { timeout: 6000 });

      await page.locator('input[id="field-def-key"]').fill('hero_title');
      await page.locator('select[id="field-def-data-type"]').selectOption('text');
      await page.locator('button:has-text("Add field")').click();
      await expect(page.locator('button:has-text("Edit")')).toHaveCount(1, { timeout: 6000 });

      // Navigate to records page — accessible
      await gotoAdmin(page, '/landing-page');
      await expect(page.locator('h1')).toContainText('Landing Page', { timeout: 6000 });

      expect(entityTypes.length).toBe(1);
      expect(fieldDefs.length).toBe(1);
    },
  );
});
