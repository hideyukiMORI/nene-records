/**
 * Category: Repetition & Long-form Stability
 *
 * Long-running scenarios that test stability over repeated create / submit
 * operations, alternating error and success cycles, and extended multi-step
 * workflows. Adapted from nene-corpus 12-repetition.spec.ts long-scenario
 * patterns — translated from "chat message repetition" to "admin CRUD
 * repetition".
 *
 * Patterns covered:
 *  - N sequential creates — all retained in list (no eviction)
 *  - Identical form data × N → N distinct items (no client-side deduplication)
 *  - Form fields cleared after each successful submit
 *  - Error/recovery × 4 alternating cycles (fail → succeed × 2)
 *  - Double-submit guard stable over N successive operations (3 clicks each)
 *  - List count grows by exactly 1 per successful create (verified per step)
 *  - Long flow: 5 creates + error mid-flow + 5 more creates → 10 items
 */

import { test, expect } from '@playwright/test';
import { bypassLogin, gotoAdmin } from '../fixtures/helpers.js';
import {
  ENTITY_TYPE_LIST_EMPTY,
  TAG_LIST_EMPTY,
  TAG_CREATED,
} from '../fixtures/api-mocks.js';

// Shared helper: build an entity-type item DTO from POST body + assigned id
function makeEntityTypeItem(id: number, name: string, slug: string) {
  return {
    id,
    name,
    slug,
    is_pinned: false,
    labels: null,
    permalink_pattern: null,
    previous_permalink_pattern: null,
  };
}

test.describe('Repetition & Long-form Stability', () => {
  // ── 15-01: 5 entity types created sequentially — all 5 retained ──────────

  test('15-01: 5 entity types created sequentially — all 5 remain visible', async ({ page }) => {
    await bypassLogin(page);

    const created: ReturnType<typeof makeEntityTypeItem>[] = [];

    await page.route('**/api/v1/entity-types**', (route) => {
      const method = route.request().method();
      if (method === 'POST') {
        const body = route.request().postDataJSON() as { name: string; slug: string };
        const item = makeEntityTypeItem(created.length + 1, body.name, body.slug);
        created.push(item);
        return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
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

    const names = ['Alpha', 'Beta', 'Gamma', 'Delta', 'Epsilon'];

    for (const name of names) {
      await page.locator('input[id="entity-type-name"]').fill(name);
      await page.locator('input[id="entity-type-slug"]').fill(name.toLowerCase());
      await page.locator('button:has-text("Create content type")').click();
      // Wait for form to reset (success indicator)
      await expect(page.locator('input[id="entity-type-name"]')).toHaveValue('', { timeout: 6000 });
    }

    // All 5 items still visible after all creates
    for (const name of names) {
      await expect(page.locator(`text=${name}`).first()).toBeVisible({ timeout: 3000 });
    }
  });

  // ── 15-02: Identical data × 3 → 3 distinct items (no client-side dedup) ──

  test('15-02: identical form data submitted 3 times → 3 distinct items created', async ({ page }) => {
    await bypassLogin(page);

    const created: ReturnType<typeof makeEntityTypeItem>[] = [];

    await page.route('**/api/v1/entity-types**', (route) => {
      const method = route.request().method();
      if (method === 'POST') {
        const body = route.request().postDataJSON() as { name: string; slug: string };
        const item = makeEntityTypeItem(created.length + 1, body.name, body.slug);
        created.push(item);
        return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
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

    const nameInput = page.locator('input[id="entity-type-name"]');
    const slugInput = page.locator('input[id="entity-type-slug"]');
    const submitBtn = page.locator('button:has-text("Create content type")');

    // Submit the same data 3 times — form resets after each success
    for (let i = 0; i < 3; i++) {
      await nameInput.fill('SameType');
      await slugInput.fill('sametype');
      await submitBtn.click();
      await expect(nameInput).toHaveValue('', { timeout: 6000 });
    }

    // 3 distinct items created → 3 Edit buttons (one per item)
    await expect(page.locator('button:has-text("Edit")')).toHaveCount(3, { timeout: 6000 });

    // Exact count in mock confirms no deduplication occurred
    expect(created.length).toBe(3);
    expect(created[0]?.id).toBe(1);
    expect(created[1]?.id).toBe(2);
    expect(created[2]?.id).toBe(3);
  });

  // ── 15-03: Form fields cleared after each successful create ───────────────

  test('15-03: name and slug inputs cleared after each of 3 entity type creates', async ({ page }) => {
    await bypassLogin(page);

    const created: ReturnType<typeof makeEntityTypeItem>[] = [];

    await page.route('**/api/v1/entity-types**', (route) => {
      const method = route.request().method();
      if (method === 'POST') {
        const body = route.request().postDataJSON() as { name: string; slug: string };
        const item = makeEntityTypeItem(created.length + 1, body.name, body.slug);
        created.push(item);
        return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
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

    const nameInput = page.locator('input[id="entity-type-name"]');
    const slugInput = page.locator('input[id="entity-type-slug"]');
    const submitBtn = page.locator('button:has-text("Create content type")');

    const pairs = [
      { name: 'First', slug: 'first' },
      { name: 'Second', slug: 'second' },
      { name: 'Third', slug: 'third' },
    ];

    for (const pair of pairs) {
      await nameInput.fill(pair.name);
      await slugInput.fill(pair.slug);

      // Confirm values are in the inputs before submit
      await expect(nameInput).toHaveValue(pair.name);
      await expect(slugInput).toHaveValue(pair.slug);

      await submitBtn.click();

      // Both inputs must be cleared after successful submit (react-hook-form reset)
      await expect(nameInput).toHaveValue('', { timeout: 6000 });
      await expect(slugInput).toHaveValue('', { timeout: 6000 });
    }
  });

  // ── 15-04: POST alternates fail/succeed × 4 — form stable throughout ──────

  test('15-04: POST alternates fail/succeed × 4 — both successes visible, form functional', async ({ page }) => {
    await bypassLogin(page);

    let postCount = 0;
    const created: ReturnType<typeof makeEntityTypeItem>[] = [];

    await page.route('**/api/v1/entity-types**', (route) => {
      const method = route.request().method();
      if (method === 'POST') {
        postCount++;
        if (postCount % 2 === 1) {
          // Odd-numbered POSTs fail
          return route.fulfill({
            status: 500,
            contentType: 'application/json',
            body: JSON.stringify({ type: 'about:blank', title: 'Server Error', status: 500, detail: 'Temporary failure', instance: '/api/v1/entity-types' }),
          });
        }
        // Even-numbered POSTs succeed
        const body = route.request().postDataJSON() as { name: string; slug: string };
        const item = makeEntityTypeItem(created.length + 1, body.name, body.slug);
        created.push(item);
        return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
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

    const nameInput = page.locator('input[id="entity-type-name"]');
    const slugInput = page.locator('input[id="entity-type-slug"]');
    const submitBtn = page.locator('button:has-text("Create content type")');

    // ── Cycle 1 ──
    await nameInput.fill('TypeA');
    await slugInput.fill('typea');

    // POST 1 → fail
    await submitBtn.click();
    await expect(submitBtn).toBeEnabled({ timeout: 3000 });
    await expect(nameInput).toBeEnabled({ timeout: 3000 });
    // Form values remain (no reset on failure)
    await expect(nameInput).toHaveValue('TypeA');

    // POST 2 → succeed (same values still in form)
    await submitBtn.click();
    await expect(page.locator('text=TypeA').first()).toBeVisible({ timeout: 6000 });
    // Form resets after success
    await expect(nameInput).toHaveValue('', { timeout: 3000 });

    // ── Cycle 2 ──
    await nameInput.fill('TypeB');
    await slugInput.fill('typeb');

    // POST 3 → fail
    await submitBtn.click();
    await expect(submitBtn).toBeEnabled({ timeout: 3000 });
    await expect(nameInput).toHaveValue('TypeB');

    // POST 4 → succeed
    await submitBtn.click();
    await expect(page.locator('text=TypeB').first()).toBeVisible({ timeout: 6000 });

    // Both items visible and form accessible
    await expect(page.locator('text=TypeA').first()).toBeVisible({ timeout: 3000 });
    await expect(page.locator('text=TypeB').first()).toBeVisible({ timeout: 3000 });
    expect(postCount).toBe(4);
  });

  // ── 15-05: Double-submit guard stable over 5 successive operations ─────────

  test('15-05: triple-click per create × 5 operations — exactly 5 POSTs total', async ({ page }) => {
    await bypassLogin(page);

    let postCallCount = 0;
    const created: ReturnType<typeof makeEntityTypeItem>[] = [];

    await page.route('**/api/v1/entity-types**', async (route) => {
      const method = route.request().method();
      if (method === 'POST') {
        postCallCount++;
        // Small artificial delay so the button stays disabled long enough for the extra clicks
        await new Promise((r) => setTimeout(r, 300));
        const body = route.request().postDataJSON() as { name: string; slug: string };
        const item = makeEntityTypeItem(created.length + 1, body.name, body.slug);
        created.push(item);
        return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
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

    const nameInput = page.locator('input[id="entity-type-name"]');
    const slugInput = page.locator('input[id="entity-type-slug"]');
    const submitBtn = page.locator('button:has-text("Create content type")');

    for (let i = 0; i < 5; i++) {
      await nameInput.fill(`Op${String(i + 1)}`);
      await slugInput.fill(`op${String(i + 1)}`);

      // First click triggers the POST; subsequent forced clicks while button is disabled
      await submitBtn.click();
      await submitBtn.click({ force: true });
      await submitBtn.click({ force: true });

      // Wait for this operation to complete (inputs cleared = form reset)
      await expect(nameInput).toHaveValue('', { timeout: 6000 });
    }

    // Despite 15 total clicks (3 × 5), only exactly 5 POSTs should have been made
    expect(postCallCount).toBe(5);
    // All 5 items visible
    await expect(page.locator('button:has-text("Edit")')).toHaveCount(5, { timeout: 6000 });
  });

  // ── 15-06: Edit button count increments by 1 per create (6 steps) ─────────

  test('15-06: Edit button count grows by exactly 1 with each of 6 sequential creates', async ({ page }) => {
    await bypassLogin(page);

    const created: ReturnType<typeof makeEntityTypeItem>[] = [];

    await page.route('**/api/v1/entity-types**', (route) => {
      const method = route.request().method();
      if (method === 'POST') {
        const body = route.request().postDataJSON() as { name: string; slug: string };
        const item = makeEntityTypeItem(created.length + 1, body.name, body.slug);
        created.push(item);
        return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
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

    const nameInput = page.locator('input[id="entity-type-name"]');
    const slugInput = page.locator('input[id="entity-type-slug"]');
    const submitBtn = page.locator('button:has-text("Create content type")');

    for (let i = 0; i < 6; i++) {
      await nameInput.fill(`Step${String(i + 1)}`);
      await slugInput.fill(`step${String(i + 1)}`);
      await submitBtn.click();

      // After each create, Edit button count should be exactly i+1
      await expect(page.locator('button:has-text("Edit")')).toHaveCount(i + 1, { timeout: 6000 });
    }
  });

  // ── 15-07: Long flow — 5 creates + error + 5 more — 10 items total ─────────

  test('15-07: 5 creates + error on 6th + retry + 4 more = 10 items total', async ({ page }) => {
    await bypassLogin(page);

    let postCount = 0;
    const created: ReturnType<typeof makeEntityTypeItem>[] = [];

    await page.route('**/api/v1/entity-types**', (route) => {
      const method = route.request().method();
      if (method === 'POST') {
        postCount++;
        if (postCount === 6) {
          // 6th attempt fails — simulates a mid-flow server error
          return route.fulfill({
            status: 503,
            contentType: 'application/json',
            body: JSON.stringify({ type: 'about:blank', title: 'Service Unavailable', status: 503, detail: 'Temporary unavailable', instance: '/api/v1/entity-types' }),
          });
        }
        const body = route.request().postDataJSON() as { name: string; slug: string };
        const item = makeEntityTypeItem(created.length + 1, body.name, body.slug);
        created.push(item);
        return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(item) });
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

    const nameInput = page.locator('input[id="entity-type-name"]');
    const slugInput = page.locator('input[id="entity-type-slug"]');
    const submitBtn = page.locator('button:has-text("Create content type")');

    // ── Phase 1: creates 1-5 ──────────────────────────────────────────────
    const phase1Names = ['Zeta', 'Eta', 'Theta', 'Iota', 'Kappa'];
    for (const name of phase1Names) {
      await nameInput.fill(name);
      await slugInput.fill(name.toLowerCase());
      await submitBtn.click();
      await expect(nameInput).toHaveValue('', { timeout: 6000 });
    }
    await expect(page.locator('button:has-text("Edit")')).toHaveCount(5, { timeout: 3000 });

    // ── Phase 2: POST 6 fails (mid-flow error) ────────────────────────────
    await nameInput.fill('Lambda');
    await slugInput.fill('lambda');
    await submitBtn.click();

    // Button re-enables after failure; form values remain intact
    await expect(submitBtn).toBeEnabled({ timeout: 3000 });
    await expect(nameInput).toHaveValue('Lambda', { timeout: 3000 });

    // POST 7 (retry of Lambda) succeeds
    await submitBtn.click();
    await expect(nameInput).toHaveValue('', { timeout: 6000 }); // form reset
    await expect(page.locator('button:has-text("Edit")')).toHaveCount(6, { timeout: 6000 });

    // ── Phase 3: creates 8-11 (4 more) ────────────────────────────────────
    const phase3Names = ['Mu', 'Nu', 'Xi', 'Omicron'];
    for (const name of phase3Names) {
      await nameInput.fill(name);
      await slugInput.fill(name.toLowerCase());
      await submitBtn.click();
      await expect(nameInput).toHaveValue('', { timeout: 6000 });
    }

    // ── Final: all 10 items visible ────────────────────────────────────────
    await expect(page.locator('button:has-text("Edit")')).toHaveCount(10, { timeout: 6000 });

    for (const name of phase1Names) {
      await expect(page.locator(`text=${name}`).first()).toBeVisible({ timeout: 3000 });
    }
    await expect(page.locator('text=Lambda').first()).toBeVisible({ timeout: 3000 });
    for (const name of phase3Names) {
      await expect(page.locator(`text=${name}`).first()).toBeVisible({ timeout: 3000 });
    }
  });

  // ── 15-08: 5 tags created in sequence — all 5 retained ───────────────────
  //
  // Mirrors 15-01 using the Tags page to verify the same "all items retained"
  // pattern holds across a different resource type.

  test('15-08: 5 tags created sequentially via the Tags page — all 5 remain visible', async ({ page }) => {
    await bypassLogin(page);

    const tagStore: { id: number; name: string; slug: string }[] = [];

    await page.route('**/api/v1/tags**', (route) => {
      const method = route.request().method();
      if (method === 'POST') {
        const body = route.request().postDataJSON() as { name: string; slug: string };
        const tag = { id: tagStore.length + 1, name: body.name, slug: body.slug };
        tagStore.push(tag);
        return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(tag) });
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

    const tagNames = ['React', 'TypeScript', 'Playwright', 'Vite', 'Node'];

    for (const name of tagNames) {
      await page.locator('input[id="tag-name"]').fill(name);
      await page.locator('input[id="tag-slug"]').fill(name.toLowerCase());
      await page.locator('button:has-text("Create tag")').click();
      await expect(page.locator('input[id="tag-name"]')).toHaveValue('', { timeout: 6000 });
    }

    // All 5 tag names visible in the list
    for (const name of tagNames) {
      await expect(page.locator(`text=${name}`).first()).toBeVisible({ timeout: 3000 });
    }
    expect(tagStore.length).toBe(5);
  });
});
