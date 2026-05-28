/**
 * Category: Full Scenarios — Landing Page & Product Sites
 *
 * Long-form E2E scenarios for LP (landing page) and product-site setups.
 * Each test follows a specific persona creating a different type of website.
 * Adapted from nene-corpus full-scenario patterns.
 *
 * Personas: Maya, Leo, Sofia, Jake, Aisha, Carlos, Emma, Noah, Olivia,
 *           Liam, Zoe, Marcus (editor), Sarah (superadmin), Ryan, Grace, Felix
 */

import { test, expect } from '@playwright/test';
import {
  bypassLogin,
  gotoAdmin,
  gotoSuperadmin,
} from '../fixtures/helpers.js';
import {
  ADMIN_TOKEN,
  ENTITY_TYPE_LIST_EMPTY,
  ENTITY_TYPE_LIST,
  TAG_LIST_EMPTY,
  TAG_CREATED,
  USER_LIST_EMPTY,
  ORGANIZATION_LIST_EMPTY,
  ORGANIZATION_LIST,
} from '../fixtures/api-mocks.js';

// ── Shared builder helpers ────────────────────────────────────────────────────

function makeEntityType(
  id: number,
  name: string,
  slug: string,
  isPinned = false,
) {
  return {
    id,
    name,
    slug,
    is_pinned: isPinned,
    labels: null,
    permalink_pattern: null,
    previous_permalink_pattern: null,
  };
}

function makeFieldDef(
  id: number,
  entityTypeId: number,
  fieldKey: string,
  dataType: string,
) {
  return {
    id,
    entity_type_id: entityTypeId,
    field_key: fieldKey,
    data_type: dataType,
    target_entity_type_id: null,
    cardinality: null,
  };
}

function makeTag(id: number, name: string) {
  return { id, slug: name.toLowerCase().replace(/\s+/g, '-'), name };
}

function makeNavItem(id: number, label: string, url: string, displayOrder = id) {
  return {
    id,
    label,
    url,
    display_order: displayOrder,
    created_at: '2026-06-01T00:00:00Z',
    updated_at: '2026-06-01T00:00:00Z',
  };
}

function makeEntity(id: number, entityTypeId: number) {
  return {
    id,
    entity_type_id: entityTypeId,
    slug: null,
    status: 'draft' as const,
    published_at: null,
    scheduled_at: null,
    is_deleted: false,
    deleted_at: null,
    meta_title: null,
    meta_description: null,
    created_at: '2026-06-01T00:00:00Z',
    updated_at: '2026-06-01T00:00:00Z',
  };
}

// ── Tests ─────────────────────────────────────────────────────────────────────

test.describe('Full Scenarios — Landing Page & Product Sites', () => {
  // ── 18-01: SaaS LP — product page entity type + 5 fields ─────────────────

  test(
    '18-01: admin Maya — create "Product Pages" type, 5 fields (hero_title/subtitle/cta_text/features/pricing_note)',
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
            body: JSON.stringify({ items: [...entityTypes], limit: 20, offset: 0 }),
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

      // Create entity type "Product Pages"
      await gotoAdmin(page, '/entity-types');
      await page.locator('input[id="entity-type-name"]').fill('Product Pages');
      await page.locator('input[id="entity-type-slug"]').fill('product-pages');
      await page.locator('button:has-text("Create content type")').click();
      await expect(page.locator('text=Product Pages').first()).toBeVisible({ timeout: 6000 });

      // Navigate to field defs page
      await page.locator('a[href*="/product-pages/fields"]').first().click();
      await expect(page).toHaveURL(/\/product-pages\/fields/, { timeout: 6000 });

      // Add 5 fields
      const fields = [
        { key: 'hero_title', type: 'text' },
        { key: 'subtitle', type: 'text' },
        { key: 'cta_text', type: 'text' },
        { key: 'features', type: 'markdown' },
        { key: 'pricing_note', type: 'text' },
      ];
      for (const field of fields) {
        await page.locator('input[id="field-def-key"]').fill(field.key);
        await page.locator('select[id="field-def-data-type"]').selectOption(field.type);
        await page.locator('button:has-text("Add field")').click();
        await expect(page.locator('input[id="field-def-key"]')).toHaveValue('', { timeout: 6000 });
      }

      // Verify exactly 5 Edit buttons
      await expect(page.locator('button:has-text("Edit")')).toHaveCount(5, { timeout: 6000 });
    },
  );

  // ── 18-02: Portfolio LP — project cards with image + relation fields ───────

  test(
    '18-02: admin Leo — create "Portfolio Items" type, 4 fields (project_name/description/demo_url/cover_image)',
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
            body: JSON.stringify({ items: [...entityTypes], limit: 20, offset: 0 }),
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

      // Create entity type "Portfolio Items"
      await gotoAdmin(page, '/entity-types');
      await page.locator('input[id="entity-type-name"]').fill('Portfolio Items');
      await page.locator('input[id="entity-type-slug"]').fill('portfolio-items');
      await page.locator('button:has-text("Create content type")').click();
      await expect(page.locator('text=Portfolio Items').first()).toBeVisible({ timeout: 6000 });

      // Navigate to fields
      await page.locator('a[href*="/portfolio-items/fields"]').first().click();
      await expect(page).toHaveURL(/\/portfolio-items\/fields/, { timeout: 6000 });

      // Add 4 fields
      const fields = [
        { key: 'project_name', type: 'text' },
        { key: 'description', type: 'markdown' },
        { key: 'demo_url', type: 'text' },
        { key: 'cover_image', type: 'image' },
      ];
      for (const field of fields) {
        await page.locator('input[id="field-def-key"]').fill(field.key);
        await page.locator('select[id="field-def-data-type"]').selectOption(field.type);
        await page.locator('button:has-text("Add field")').click();
        await expect(page.locator('input[id="field-def-key"]')).toHaveValue('', { timeout: 6000 });
      }

      // Verify exactly 4 Edit buttons
      await expect(page.locator('button:has-text("Edit")')).toHaveCount(4, { timeout: 6000 });
    },
  );

  // ── 18-03: Event site — entity type + datetime + int fields ───────────────

  test(
    '18-03: admin Sofia — create "Events" type, 5 fields including datetime and int — 5 Edit buttons',
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
            body: JSON.stringify({ items: [...entityTypes], limit: 20, offset: 0 }),
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

      // Create entity type "Events"
      await gotoAdmin(page, '/entity-types');
      await page.locator('input[id="entity-type-name"]').fill('Events');
      await page.locator('input[id="entity-type-slug"]').fill('events');
      await page.locator('button:has-text("Create content type")').click();
      await expect(page.locator('text=Events').first()).toBeVisible({ timeout: 6000 });

      // Navigate to fields
      await page.locator('a[href*="/events/fields"]').first().click();
      await expect(page).toHaveURL(/\/events\/fields/, { timeout: 6000 });

      // Add 5 fields including datetime and int
      const fields = [
        { key: 'event_name', type: 'text' },
        { key: 'event_date', type: 'datetime' },
        { key: 'capacity', type: 'int' },
        { key: 'venue', type: 'text' },
        { key: 'speakers', type: 'markdown' },
      ];
      for (const field of fields) {
        await page.locator('input[id="field-def-key"]').fill(field.key);
        await page.locator('select[id="field-def-data-type"]').selectOption(field.type);
        await page.locator('button:has-text("Add field")').click();
        await expect(page.locator('input[id="field-def-key"]')).toHaveValue('', { timeout: 6000 });
      }

      // Verify exactly 5 Edit buttons
      await expect(page.locator('button:has-text("Edit")')).toHaveCount(5, { timeout: 6000 });
    },
  );

  // ── 18-04: E-commerce product page — with bool and int fields ─────────────

  test(
    '18-04: admin Jake — create "Products" type, 4 fields (name/price/description/in_stock) — 4 Edit buttons',
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
            body: JSON.stringify({ items: [...entityTypes], limit: 20, offset: 0 }),
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

      // Create entity type "Products"
      await gotoAdmin(page, '/entity-types');
      await page.locator('input[id="entity-type-name"]').fill('Products');
      await page.locator('input[id="entity-type-slug"]').fill('products');
      await page.locator('button:has-text("Create content type")').click();
      await expect(page.locator('text=Products').first()).toBeVisible({ timeout: 6000 });

      // Navigate to fields
      await page.locator('a[href*="/products/fields"]').first().click();
      await expect(page).toHaveURL(/\/products\/fields/, { timeout: 6000 });

      // Add 4 fields including bool and int
      const fields = [
        { key: 'name', type: 'text' },
        { key: 'price', type: 'int' },
        { key: 'description', type: 'markdown' },
        { key: 'in_stock', type: 'bool' },
      ];
      for (const field of fields) {
        await page.locator('input[id="field-def-key"]').fill(field.key);
        await page.locator('select[id="field-def-data-type"]').selectOption(field.type);
        await page.locator('button:has-text("Add field")').click();
        await expect(page.locator('input[id="field-def-key"]')).toHaveValue('', { timeout: 6000 });
      }

      // Verify exactly 4 Edit buttons
      await expect(page.locator('button:has-text("Edit")')).toHaveCount(4, { timeout: 6000 });
    },
  );

  // ── 18-05: Documentation site — with enum and markdown fields ─────────────

  test(
    '18-05: admin Aisha — create "Docs Pages" type, 4 fields (title/content/category/difficulty) — 4 Edit buttons',
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
            body: JSON.stringify({ items: [...entityTypes], limit: 20, offset: 0 }),
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

      // Create entity type "Docs Pages"
      await gotoAdmin(page, '/entity-types');
      await page.locator('input[id="entity-type-name"]').fill('Docs Pages');
      await page.locator('input[id="entity-type-slug"]').fill('docs-pages');
      await page.locator('button:has-text("Create content type")').click();
      await expect(page.locator('text=Docs Pages').first()).toBeVisible({ timeout: 6000 });

      // Navigate to fields
      await page.locator('a[href*="/docs-pages/fields"]').first().click();
      await expect(page).toHaveURL(/\/docs-pages\/fields/, { timeout: 6000 });

      // Add 4 fields including 2 enums
      const fields = [
        { key: 'title', type: 'text' },
        { key: 'content', type: 'markdown' },
        { key: 'category', type: 'enum' },
        { key: 'difficulty', type: 'enum' },
      ];
      for (const field of fields) {
        await page.locator('input[id="field-def-key"]').fill(field.key);
        await page.locator('select[id="field-def-data-type"]').selectOption(field.type);
        await page.locator('button:has-text("Add field")').click();
        await expect(page.locator('input[id="field-def-key"]')).toHaveValue('', { timeout: 6000 });
      }

      // Verify exactly 4 Edit buttons
      await expect(page.locator('button:has-text("Edit")')).toHaveCount(4, { timeout: 6000 });
    },
  );

  // ── 18-06: LP + Blog combination site — 2 entity types ───────────────────

  test(
    '18-06: admin Carlos — create "Landing Pages" and "Blog Posts" sequentially — both fields pages accessible',
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
            body: JSON.stringify({ items: [...entityTypes], limit: 20, offset: 0 }),
          });
        }
        return route.continue();
      });

      await page.route('**/api/v1/field-defs**', (route) => {
        if (route.request().method() === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [], limit: 20, offset: 0 }),
          });
        }
        return route.continue();
      });

      await gotoAdmin(page, '/entity-types');

      // Create "Landing Pages"
      await page.locator('input[id="entity-type-name"]').fill('Landing Pages');
      await page.locator('input[id="entity-type-slug"]').fill('landing-pages');
      await page.locator('button:has-text("Create content type")').click();
      await expect(page.locator('input[id="entity-type-name"]')).toHaveValue('', { timeout: 6000 });

      // Create "Blog Posts"
      await page.locator('input[id="entity-type-name"]').fill('Blog Posts');
      await page.locator('input[id="entity-type-slug"]').fill('blog-posts');
      await page.locator('button:has-text("Create content type")').click();
      await expect(page.locator('input[id="entity-type-name"]')).toHaveValue('', { timeout: 6000 });

      // Both visible in list
      await expect(page.locator('text=Landing Pages').first()).toBeVisible({ timeout: 6000 });
      await expect(page.locator('text=Blog Posts').first()).toBeVisible({ timeout: 6000 });
      expect(entityTypes.length).toBe(2);

      // Navigate to LP fields page
      await gotoAdmin(page, '/entity-types/landing-pages/fields');
      await expect(page).toHaveURL(/\/landing-pages\/fields/, { timeout: 6000 });

      // Navigate to Blog Posts fields page
      await gotoAdmin(page, '/entity-types/blog-posts/fields');
      await expect(page).toHaveURL(/\/blog-posts\/fields/, { timeout: 6000 });
    },
  );

  // ── 18-07: LP with multiple navigation links — 3 nav items ────────────────

  test(
    '18-07: admin Emma — create "Landing Pages" entity type → add 3 nav items (Home/Features/Pricing) → all visible',
    async ({ page }) => {
      await bypassLogin(page);

      const entityTypes: ReturnType<typeof makeEntityType>[] = [];
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
            body: JSON.stringify({ items: [...entityTypes], limit: 20, offset: 0 }),
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

      // Create entity type "Landing Pages"
      await gotoAdmin(page, '/entity-types');
      await page.locator('input[id="entity-type-name"]').fill('Landing Pages');
      await page.locator('input[id="entity-type-slug"]').fill('landing-pages');
      await page.locator('button:has-text("Create content type")').click();
      await expect(page.locator('text=Landing Pages').first()).toBeVisible({ timeout: 6000 });

      // Navigate to navigation page
      await gotoAdmin(page, '/navigation');
      await expect(page.locator('h1')).toContainText('Menus', { timeout: 6000 });

      // Add 3 nav items
      const navEntries = [
        { label: 'Home', url: '/' },
        { label: 'Features', url: '/features' },
        { label: 'Pricing', url: '/pricing' },
      ];
      for (const entry of navEntries) {
        await page.locator('input[id="nav-create-label"]').fill(entry.label);
        await page.locator('input[id="nav-create-url"]').fill(entry.url);
        await page.locator('button:has-text("Save")').click();
        await expect(page.locator('input[id="nav-create-label"]')).toHaveValue('', { timeout: 6000 });
      }

      // All 3 nav items visible
      for (const entry of navEntries) {
        await expect(page.locator(`text=${entry.label}`).first()).toBeVisible({ timeout: 6000 });
      }
      expect(navItems.length).toBe(3);
    },
  );

  // ── 18-08: LP error recovery — field add fails then succeeds ──────────────

  test(
    '18-08: admin Noah — create "Product Pages" → field POST fails (500) → form stays → retry succeeds',
    async ({ page }) => {
      await bypassLogin(page);

      const entityTypes: ReturnType<typeof makeEntityType>[] = [];
      const fieldDefs: ReturnType<typeof makeFieldDef>[] = [];
      let fieldPostCount = 0;

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
            body: JSON.stringify({ items: [...entityTypes], limit: 20, offset: 0 }),
          });
        }
        return route.continue();
      });

      await page.route('**/api/v1/field-defs**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          fieldPostCount++;
          if (fieldPostCount === 1) {
            // First attempt fails with 500
            return route.fulfill({
              status: 500,
              contentType: 'application/json',
              body: JSON.stringify({
                type: 'about:blank',
                title: 'Internal Server Error',
                status: 500,
                detail: 'Temporary failure',
                instance: '/api/v1/field-defs',
              }),
            });
          }
          // Second attempt succeeds
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

      // Create entity type
      await gotoAdmin(page, '/entity-types');
      await page.locator('input[id="entity-type-name"]').fill('Product Pages');
      await page.locator('input[id="entity-type-slug"]').fill('product-pages');
      await page.locator('button:has-text("Create content type")').click();
      await expect(page.locator('text=Product Pages').first()).toBeVisible({ timeout: 6000 });

      // Navigate to fields
      await page.locator('a[href*="/product-pages/fields"]').first().click();
      await expect(page).toHaveURL(/\/product-pages\/fields/, { timeout: 6000 });

      // First attempt — fails
      await page.locator('input[id="field-def-key"]').fill('headline');
      await page.locator('select[id="field-def-data-type"]').selectOption('text');
      await page.locator('button:has-text("Add field")').click();
      await page.waitForTimeout(500);

      // Form must still be accessible after failure
      await expect(page.locator('input[id="field-def-key"]')).toBeVisible({ timeout: 3000 });

      // Second attempt — succeeds (re-fill in case form was cleared)
      await page.locator('input[id="field-def-key"]').fill('headline');
      await page.locator('select[id="field-def-data-type"]').selectOption('text');
      await page.locator('button:has-text("Add field")').click();
      await expect(page.locator('input[id="field-def-key"]')).toHaveValue('', { timeout: 6000 });

      // Field successfully created — 1 Edit button
      await expect(page.locator('button:has-text("Edit")')).toHaveCount(1, { timeout: 6000 });
    },
  );

  // ── 18-09: Media + LP workflow — LP entity type + verify media page ────────

  test(
    '18-09: admin Olivia — create "Media-rich LPs" entity type → navigate to /admin/media → h1 "Media library"',
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
            body: JSON.stringify({ items: [...entityTypes], limit: 20, offset: 0 }),
          });
        }
        return route.continue();
      });

      await page.route('**/api/v1/media**', (route) => {
        if (route.request().method() === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [], limit: 20, offset: 0, total: 0 }),
          });
        }
        return route.continue();
      });

      // Create entity type "Media-rich LPs"
      await gotoAdmin(page, '/entity-types');
      await page.locator('input[id="entity-type-name"]').fill('Media-rich LPs');
      await page.locator('input[id="entity-type-slug"]').fill('media-rich-lps');
      await page.locator('button:has-text("Create content type")').click();
      await expect(page.locator('text=Media-rich LPs').first()).toBeVisible({ timeout: 6000 });

      // Navigate to media page
      await gotoAdmin(page, '/media');
      await expect(page.locator('h1')).toContainText('Media library', { timeout: 6000 });
    },
  );

  // ── 18-10: Records for LP — create LP entity type → New item → POST entities

  test(
    '18-10: admin Liam — create "Campaign Pages" → navigate to records → "New item" clicked → POST /entities called',
    async ({ page }) => {
      await bypassLogin(page);

      const entityTypes: ReturnType<typeof makeEntityType>[] = [];
      const entities: ReturnType<typeof makeEntity>[] = [];

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
            body: JSON.stringify({ items: [...entityTypes], limit: 20, offset: 0 }),
          });
        }
        return route.continue();
      });

      await page.route('**/api/v1/entities**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          const entityTypeId = entityTypes[0]?.id ?? 1;
          const item = makeEntity(entities.length + 1, entityTypeId);
          entities.push(item);
          return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
        }
        if (method === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...entities], limit: 20, offset: 0, total: entities.length }),
          });
        }
        return route.continue();
      });

      await page.route('**/api/v1/entities/**', (route) => {
        if (route.request().method() === 'GET') {
          const entity = entities[0];
          if (entity) {
            return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(entity) });
          }
        }
        return route.continue();
      });

      // Create entity type "Campaign Pages"
      await gotoAdmin(page, '/entity-types');
      await page.locator('input[id="entity-type-name"]').fill('Campaign Pages');
      await page.locator('input[id="entity-type-slug"]').fill('campaign-pages');
      await page.locator('button:has-text("Create content type")').click();
      await expect(page.locator('text=Campaign Pages').first()).toBeVisible({ timeout: 6000 });

      // Navigate to records page
      await gotoAdmin(page, '/campaign-pages');
      await expect(page.locator('h1')).toContainText('Campaign Pages', { timeout: 6000 });

      // "New item" button visible
      await expect(page.locator('button:has-text("New item")')).toBeVisible({ timeout: 6000 });

      // Click New item — POST /api/v1/entities called
      await page.locator('button:has-text("New item")').click();

      // Wait for entity to be created (POST called)
      await page.waitForTimeout(500);
      expect(entities.length).toBe(1);
    },
  );

  // ── 18-11: 3 LP types for multi-product company ───────────────────────────

  test(
    '18-11: admin Zoe — create "Features Page", "Pricing Page", "About Page" — all 3 visible, 3 Edit buttons',
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
            body: JSON.stringify({ items: [...entityTypes], limit: 20, offset: 0 }),
          });
        }
        return route.continue();
      });

      await gotoAdmin(page, '/entity-types');

      // Create 3 entity types sequentially
      const types = [
        { name: 'Features Page', slug: 'features-page' },
        { name: 'Pricing Page', slug: 'pricing-page' },
        { name: 'About Page', slug: 'about-page' },
      ];
      for (const type of types) {
        await page.locator('input[id="entity-type-name"]').fill(type.name);
        await page.locator('input[id="entity-type-slug"]').fill(type.slug);
        await page.locator('button:has-text("Create content type")').click();
        await expect(page.locator('input[id="entity-type-name"]')).toHaveValue('', { timeout: 6000 });
      }

      // All 3 visible
      for (const type of types) {
        await expect(page.locator(`text=${type.name}`).first()).toBeVisible({ timeout: 6000 });
      }

      // Exactly 3 Edit buttons (one per entity type)
      await expect(page.locator('button:has-text("Edit")')).toHaveCount(3, { timeout: 6000 });
      expect(entityTypes.length).toBe(3);
    },
  );

  // ── 18-12: Editor on LP site — cannot manage schema, records accessible ────

  test(
    '18-12: editor Marcus — entity-types create form NOT visible, LP records page accessible',
    async ({ page }) => {
      const existingType = makeEntityType(1, 'Landing Pages', 'landing-pages');

      // bypassLogin with editor role; pre-existing LP entity type as sidebar list
      await bypassLogin(page, {
        role: 'editor',
        entityTypes: { items: [existingType], limit: 20, offset: 0 },
      });

      // Override entity-types endpoint (LIFO) — returns the existing type for slug lookup
      await page.route('**/api/v1/entity-types**', (route) => {
        if (route.request().method() === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [existingType], limit: 20, offset: 0 }),
          });
        }
        return route.continue();
      });

      // Entities list for records page
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

      // entity-types page: create form NOT visible for editor
      await gotoAdmin(page, '/entity-types');
      await expect(page.locator('input[id="entity-type-name"]')).not.toBeVisible({ timeout: 6000 });

      // Records page for the existing LP type is still accessible
      await gotoAdmin(page, '/landing-pages');
      await expect(page.locator('h1')).toBeVisible({ timeout: 6000 });
    },
  );

  // ── 18-13: Superadmin oversees LP setup across orgs ───────────────────────

  test(
    '18-13: superadmin Sarah — organizations list loads → navigate to admin area → dashboard accessible',
    async ({ page }) => {
      await bypassLogin(page, { role: 'superadmin' });

      // Mock organizations endpoint
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

      // Mock entity-types for admin area
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

      // Navigate to superadmin organizations
      await gotoSuperadmin(page, '/organizations');
      await expect(page.locator('text=Acme Corp').first()).toBeVisible({ timeout: 6000 });
      await expect(page.locator('text=Globex Inc').first()).toBeVisible({ timeout: 6000 });

      // Navigate to admin area — dashboard should be accessible
      await gotoAdmin(page, '/entity-types');
      await expect(page).toHaveURL(/\/admin/, { timeout: 6000 });
    },
  );

  // ── 18-14: LP with tags — create LP type + 4 tags ─────────────────────────

  test(
    '18-14: admin Ryan — create "Campaign Pages" entity type → add 4 tags (launch/promo/seasonal/featured) → all visible',
    async ({ page }) => {
      await bypassLogin(page);

      const entityTypes: ReturnType<typeof makeEntityType>[] = [];
      const tagStore: ReturnType<typeof makeTag>[] = [];

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
            body: JSON.stringify({ items: [...entityTypes], limit: 20, offset: 0 }),
          });
        }
        return route.continue();
      });

      await page.route('**/api/v1/tags**', (route) => {
        const method = route.request().method();
        if (method === 'POST') {
          const body = route.request().postDataJSON() as { name: string; slug: string };
          const item = makeTag(tagStore.length + 1, body.name);
          tagStore.push(item);
          return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
        }
        if (method === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [...tagStore], limit: 20, offset: 0 }),
          });
        }
        return route.continue();
      });

      // Create entity type "Campaign Pages"
      await gotoAdmin(page, '/entity-types');
      await page.locator('input[id="entity-type-name"]').fill('Campaign Pages');
      await page.locator('input[id="entity-type-slug"]').fill('campaign-pages');
      await page.locator('button:has-text("Create content type")').click();
      await expect(page.locator('text=Campaign Pages').first()).toBeVisible({ timeout: 6000 });

      // Navigate to tags page
      await gotoAdmin(page, '/tags');
      await expect(page.locator('h1')).toContainText('Tags', { timeout: 6000 });

      // Create 4 tags
      const tagNames = ['launch', 'promo', 'seasonal', 'featured'];
      for (const name of tagNames) {
        await page.locator('input[id="tag-name"]').fill(name);
        await page.locator('input[id="tag-slug"]').fill(name);
        await page.locator('button:has-text("Create tag")').click();
        await expect(page.locator('input[id="tag-name"]')).toHaveValue('', { timeout: 6000 });
      }

      // All 4 tags visible
      for (const name of tagNames) {
        await expect(page.locator(`text=${name}`).first()).toBeVisible({ timeout: 6000 });
      }
      expect(tagStore.length).toBe(4);
    },
  );

  // ── 18-15: Full LP setup teardown — delete entity type → Blog remains ──────

  test(
    '18-15: admin Grace — 2 entity types (LP + Blog) → delete LP → confirm → only Blog remains',
    async ({ page }) => {
      const lpType = makeEntityType(1, 'Landing Pages', 'landing-pages');
      const blogType = makeEntityType(2, 'Blog Posts', 'blog-posts');

      await bypassLogin(page, {
        entityTypes: { items: [lpType, blogType], limit: 20, offset: 0 },
      });

      let deleted = false;

      // Override entity-types list handler (LIFO)
      await page.route('**/api/v1/entity-types**', (route) => {
        if (route.request().method() === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(
              deleted
                ? { items: [blogType], limit: 20, offset: 0 }
                : { items: [lpType, blogType], limit: 20, offset: 0 },
            ),
          });
        }
        return route.continue();
      });

      // DELETE endpoint (path-based pattern)
      await page.route('**/api/v1/entity-types/**', (route) => {
        if (route.request().method() === 'DELETE') {
          deleted = true;
          return route.fulfill({ status: 204, body: '' });
        }
        return route.continue();
      });

      await gotoAdmin(page, '/entity-types');
      await expect(page.locator('text=Landing Pages').first()).toBeVisible({ timeout: 6000 });
      await expect(page.locator('text=Blog Posts').first()).toBeVisible({ timeout: 6000 });

      // Initially 2 Edit buttons
      await expect(page.locator('button:has-text("Edit")')).toHaveCount(2, { timeout: 6000 });

      // Click Delete on the first item (Landing Pages)
      await page.locator('button:has-text("Delete")').first().click();

      // Confirm dialog appears
      await expect(page.locator('text=Delete content type?')).toBeVisible({ timeout: 3000 });

      // Confirm the deletion
      await page.locator('[role="dialog"] button:has-text("Delete")').click();

      // Only Blog Posts remains — exactly 1 Edit button
      await expect(page.locator('button:has-text("Edit")')).toHaveCount(1, { timeout: 6000 });
      await expect(page.locator('text=Blog Posts').first()).toBeVisible({ timeout: 6000 });
    },
  );

  // ── 18-16: Settings page after LP setup ───────────────────────────────────

  test(
    '18-16: admin Felix — create "Landing Pages" → navigate to /settings → h1 "Site settings" → back → LP still in list',
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
            body: JSON.stringify({ items: [...entityTypes], limit: 20, offset: 0 }),
          });
        }
        return route.continue();
      });

      await page.route('**/api/v1/settings**', (route) => {
        if (route.request().method() === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [] }),
          });
        }
        return route.continue();
      });

      await page.route('**/api/v1/public/settings**', (route) => {
        if (route.request().method() === 'GET') {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ items: [] }),
          });
        }
        return route.continue();
      });

      // Create entity type "Landing Pages"
      await gotoAdmin(page, '/entity-types');
      await page.locator('input[id="entity-type-name"]').fill('Landing Pages');
      await page.locator('input[id="entity-type-slug"]').fill('landing-pages');
      await page.locator('button:has-text("Create content type")').click();
      await expect(page.locator('text=Landing Pages').first()).toBeVisible({ timeout: 6000 });

      // Navigate to settings — should still work
      await gotoAdmin(page, '/settings');
      await expect(page.locator('h1')).toContainText('Site settings', { timeout: 6000 });

      // Navigate back to entity-types — LP still in list
      await gotoAdmin(page, '/entity-types');
      await expect(page.locator('text=Landing Pages').first()).toBeVisible({ timeout: 6000 });
      expect(entityTypes.length).toBe(1);
    },
  );
});
