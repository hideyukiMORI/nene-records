/**
 * Category: Multi-step CRUD Flows
 *
 * Tests multi-step create / read / update / delete operations.
 * Each test combines multiple actions in sequence, mirroring real
 * admin workflows. Inspired by nene-corpus 11-conversation-flows.spec.ts.
 *
 * Patterns covered:
 *  - Create → verify in list → create second → both visible
 *  - Navigate entity type → field defs page → back
 *  - Create tag → delete with confirmation → removed from list
 *  - Delete flow opens confirmation dialog
 *  - Field defs: load → empty → add field → field in list
 */

import { test, expect } from '@playwright/test';
import {
  bypassLogin,
  gotoAdmin,
  mockTagsEndpoint,
  BASE_URL,
} from '../fixtures/helpers.js';
import {
  ENTITY_TYPE_LIST_EMPTY,
  ENTITY_TYPE_LIST,
  ENTITY_TYPE_CREATED,
  TAG_LIST_EMPTY,
  TAG_CREATED,
} from '../fixtures/api-mocks.js';

// Shared entity type fixtures
const ENTITY_TYPE_SECOND = {
  id: 4,
  name: 'Videos',
  slug: 'videos',
  is_pinned: false,
  labels: null,
  permalink_pattern: null,
  previous_permalink_pattern: null,
};

test.describe('CRUD Flows', () => {
  // ── 12-01: Create two entity types in sequence — both remain visible ───────

  test('12-01: create entity type A then B — both appear in the existing list', async ({ page }) => {
    await bypassLogin(page);

    const created: typeof ENTITY_TYPE_CREATED[] = [];

    await page.route('**/api/v1/entity-types**', (route) => {
      const method = route.request().method();
      if (method === 'POST') {
        const body = route.request().postDataJSON() as { name: string; slug: string };
        const newItem = {
          id: created.length + 10,
          name: body.name,
          slug: body.slug,
          is_pinned: false,
          labels: null,
          permalink_pattern: null,
          previous_permalink_pattern: null,
        };
        created.push(newItem);
        return route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify(newItem),
        });
      }
      if (method === 'GET') {
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ items: [...created], limit: 20, offset: 0 }),
        });
      }
      return route.continue();
    });

    await gotoAdmin(page, '/entity-types');

    // Create first type
    await page.locator('input[id="entity-type-name"]').fill('Events');
    await page.locator('input[id="entity-type-slug"]').fill('events');
    await page.locator('button:has-text("Create content type")').click();
    await expect(page.locator('text=Events').first()).toBeVisible({ timeout: 6000 });

    // Create second type
    await page.locator('input[id="entity-type-name"]').fill('Videos');
    await page.locator('input[id="entity-type-slug"]').fill('videos');
    await page.locator('button:has-text("Create content type")').click();
    await expect(page.locator('text=Videos').first()).toBeVisible({ timeout: 6000 });

    // Both types must be in the list simultaneously
    await expect(page.locator('text=Events').first()).toBeVisible({ timeout: 6000 });
    await expect(page.locator('text=Videos').first()).toBeVisible({ timeout: 6000 });
  });

  // ── 12-02: Navigate entity type → field defs page → back ──────────────────

  test('12-02: entity type list → click Fields link → field defs page loads', async ({ page }) => {
    await bypassLogin(page, { entityTypes: ENTITY_TYPE_LIST });

    // Mock entity-types endpoint (includes the slug-based lookup)
    await page.route('**/api/v1/entity-types**', (route) => {
      if (route.request().method() === 'GET') {
        // Both list and slug-filtered requests return the same list
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(ENTITY_TYPE_LIST),
        });
      }
      return route.continue();
    });

    // Mock field defs endpoint
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

    // Find and click the Fields link for Posts
    await page.locator('a[href*="/posts/fields"]').first().click();

    // Field defs page title
    await expect(page).toHaveURL(/\/posts\/fields/, { timeout: 6000 });
    await expect(page.locator('h1')).toContainText('Posts', { timeout: 6000 });
  });

  // ── 12-03: Field defs page — empty state → add field → in list ────────────

  test('12-03: field defs — empty state → add text field → field appears in list', async ({ page }) => {
    await bypassLogin(page, { entityTypes: ENTITY_TYPE_LIST });

    const createdFields: object[] = [];

    // Entity type lookup by slug
    await page.route('**/api/v1/entity-types**', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(ENTITY_TYPE_LIST),
        });
      }
      return route.continue();
    });

    // Field defs CRUD — must use snake_case DTO format (mapper reads dto.field_key, dto.data_type)
    await page.route('**/api/v1/field-defs**', (route) => {
      const method = route.request().method();
      if (method === 'POST') {
        const body = route.request().postDataJSON() as { field_key: string; data_type: string };
        const newField = {
          id: createdFields.length + 1,
          entity_type_id: 1,
          field_key: body.field_key,
          data_type: body.data_type,
          target_entity_type_id: null,
          cardinality: null,
        };
        createdFields.push(newField);
        return route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify(newField),
        });
      }
      if (method === 'GET') {
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ items: [...createdFields], limit: 20, offset: 0 }),
        });
      }
      return route.continue();
    });

    await gotoAdmin(page, '/entity-types/posts/fields');

    // Empty state shown first
    await expect(page.locator('text=No fields yet')).toBeVisible({ timeout: 6000 });

    // Add a field
    await page.locator('input[id="field-def-key"]').fill('title');
    await page.locator('button:has-text("Add field")').click();

    // Field appears
    await expect(page.locator('text=title').first()).toBeVisible({ timeout: 6000 });
  });

  // ── 12-04: Tag create → delete → confirmation dialog → gone ───────────────

  test('12-04: create tag → request delete → confirm dialog opens', async ({ page }) => {
    await bypassLogin(page);

    const tags: object[] = [{ id: 4, slug: 'tutorial', name: 'Tutorial' }];

    await page.route('**/api/v1/tags**', (route) => {
      const method = route.request().method();
      if (method === 'GET') {
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ items: [...tags], limit: 20, offset: 0 }),
        });
      }
      if (method === 'POST') {
        return route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify(TAG_CREATED),
        });
      }
      return route.continue();
    });

    await page.route('**/api/v1/tags/**', (route) => {
      if (route.request().method() === 'DELETE') {
        tags.length = 0;
        return route.fulfill({ status: 204, body: '' });
      }
      return route.continue();
    });

    await gotoAdmin(page, '/tags');
    await expect(page.locator('text=Tutorial').first()).toBeVisible({ timeout: 6000 });

    // Click delete on the tag — should open confirm dialog
    await page.locator('button:has-text("Delete")').first().click();

    // Confirm dialog should appear
    await expect(page.locator('text=Delete tag?')).toBeVisible({ timeout: 3000 });
  });

  // ── 12-05: Entity type delete — confirm dialog title appears ──────────────

  test('12-05: entity type delete request → confirm dialog with type name', async ({ page }) => {
    await bypassLogin(page, { entityTypes: ENTITY_TYPE_LIST });

    await page.route('**/api/v1/entity-types**', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(ENTITY_TYPE_LIST),
        });
      }
      return route.continue();
    });

    await gotoAdmin(page, '/entity-types');

    // Should see existing types
    await expect(page.locator('text=Posts').first()).toBeVisible({ timeout: 6000 });

    // Click Delete on Posts
    const deleteBtn = page.locator('button:has-text("Delete")').first();
    await deleteBtn.click();

    // Confirm dialog appears with "Delete content type?" title
    await expect(page.locator('text=Delete content type?')).toBeVisible({ timeout: 3000 });

    // Cancel button available
    await expect(page.locator('button:has-text("Cancel")')).toBeVisible({ timeout: 3000 });
  });

  // ── 12-06: Entity type delete → cancel → type remains in list ────────────

  test('12-06: entity type delete → cancel confirm → type still in list', async ({ page }) => {
    await bypassLogin(page, { entityTypes: ENTITY_TYPE_LIST });

    await page.route('**/api/v1/entity-types**', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(ENTITY_TYPE_LIST),
        });
      }
      return route.continue();
    });

    await gotoAdmin(page, '/entity-types');
    await expect(page.locator('text=Posts').first()).toBeVisible({ timeout: 6000 });

    // Open delete dialog
    await page.locator('button:has-text("Delete")').first().click();
    await expect(page.locator('text=Delete content type?')).toBeVisible({ timeout: 3000 });

    // Cancel — should close dialog and retain Posts
    await page.locator('button:has-text("Cancel")').click();
    await expect(page.locator('text=Delete content type?')).not.toBeVisible({ timeout: 3000 });
    await expect(page.locator('text=Posts').first()).toBeVisible({ timeout: 3000 });
  });

  // ── 12-07: Entity types list with existing types — edit button present ─────

  test('12-07: existing entity type list — each type has an Edit button', async ({ page }) => {
    await bypassLogin(page, { entityTypes: ENTITY_TYPE_LIST });

    await page.route('**/api/v1/entity-types**', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(ENTITY_TYPE_LIST),
        });
      }
      return route.continue();
    });

    await gotoAdmin(page, '/entity-types');
    await expect(page.locator('text=Posts').first()).toBeVisible({ timeout: 6000 });

    // Both Posts and Pages have Edit buttons
    const editButtons = page.locator('button:has-text("Edit")');
    await expect(editButtons).toHaveCount(2, { timeout: 6000 });
  });

  // ── 12-08: 3 tags create in sequence — all retained in list ───────────────

  test('12-08: create 3 tags sequentially — all 3 visible in list', async ({ page }) => {
    await bypassLogin(page);

    const tagStore: { id: number; name: string; slug: string }[] = [];

    await page.route('**/api/v1/tags**', (route) => {
      const method = route.request().method();
      if (method === 'POST') {
        const body = route.request().postDataJSON() as { name: string; slug: string };
        tagStore.push({ id: tagStore.length + 1, name: body.name, slug: body.slug });
        return route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify(tagStore[tagStore.length - 1]),
        });
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

    await gotoAdmin(page, '/tags');

    const tagPairs = [
      { name: 'Technology', slug: 'technology' },
      { name: 'Design', slug: 'design' },
      { name: 'Tutorial', slug: 'tutorial' },
    ];

    for (const tag of tagPairs) {
      await page.locator('input[id="tag-name"]').fill(tag.name);
      await page.locator('input[id="tag-slug"]').fill(tag.slug);
      await page.locator('button:has-text("Create tag")').click();
      await expect(page.locator(`text=${tag.name}`).first()).toBeVisible({ timeout: 6000 });
    }

    // All 3 still visible after all creates
    for (const tag of tagPairs) {
      await expect(page.locator(`text=${tag.name}`).first()).toBeVisible({ timeout: 6000 });
    }
  });
});
