/**
 * Category: Multi-tenancy Full Scenarios
 *
 * エンドツーエンドの完全シナリオでマルチテナント機能を総合検証する。
 *
 * Level 4 — 完全シナリオブラウザテスト
 * - superadmin が org を作成し、admin を招待し、admin がログイン後 org スコープのユーザーを管理するフロー
 * - クロス org 分離: 2 組織の admin はそれぞれ自 org のユーザーのみ参照できること
 * - superadmin 監督: superadmin は全 org・全ユーザーにアクセスできること
 *
 * すべての API 呼び出しはモックで完結（バックエンド不要）。
 */

import { test, expect } from '@playwright/test';
import {
  bypassLogin,
  gotoAdmin,
  gotoSuperadmin,
  mockUsersEndpoint,
  mockOrganizationsEndpoint,
  BASE_URL,
} from '../fixtures/helpers.js';
import {
  ORGANIZATION_LIST,
  ORGANIZATION_LIST_EMPTY,
  ORGANIZATION_DETAIL,
  USER_LIST_EMPTY,
  USER_LIST_WITH_ORG,
  USER_LIST_ORG2,
  USER_LIST,
} from '../fixtures/api-mocks.js';

test.describe('Multi-tenancy — Full Scenarios', () => {
  // ── 08-01: superadmin onboarding → org admin sees scoped users ────────────────

  test('08-01: full onboarding — superadmin creates org, org admin sees only own org users', async ({ page }) => {
    // ═══ Phase 1: superadmin が組織を作成 ═════════════════════════════════════
    await bypassLogin(page, { role: 'superadmin' });

    const newOrg = { id: 5, name: 'TechStart', slug: 'techstart', plan: 'free', is_active: true, custom_domain: null, created_at: '2026-06-01T00:00:00Z', updated_at: '2026-06-01T00:00:00Z' };

    let orgCreated = false;
    await page.route('**/api/v1/organizations**', (route) => {
      const method = route.request().method();
      if (method === 'POST') {
        orgCreated = true;
        return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(newOrg) });
      }
      if (method === 'GET') {
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(orgCreated
            ? { data: [newOrg], meta: { total: 1, limit: 50, offset: 0 } }
            : { data: [], meta: { total: 0, limit: 50, offset: 0 } }),
        });
      }
      return route.continue();
    });

    await gotoSuperadmin(page, '/organizations');
    await page.locator('button:has-text("New Organization")').click();
    await page.locator('input[id="org-name"]').fill('TechStart');
    await page.locator('input[id="org-slug"]').fill('techstart');
    await page.locator('button[type="submit"]:has-text("Create")').click();
    await page.waitForTimeout(500);
    expect(orgCreated).toBe(true);

    // ═══ Phase 2: org admin としてログイン後、org スコープユーザーを参照 ══════
    // セッションをリセットして org admin として再ログイン
    await page.evaluate(() => { localStorage.clear(); });

    await bypassLogin(page, { role: 'admin', email: 'techstart-admin@example.com', orgId: 5 });
    await mockUsersEndpoint(page, {
      users: [
        { id: 10, email: 'techstart-admin@example.com', role: 'admin', status: 'active', organization_id: 5, created_at: '2026-06-01T00:00:00Z', updated_at: '2026-06-01T00:00:00Z' },
      ],
    });
    await gotoAdmin(page, '/users');

    // org admin は自 org の admin だけ見える
    await expect(page.locator('text=techstart-admin@example.com').first()).toBeVisible({ timeout: 6000 });
  });

  // ── 08-02: cross-org isolation — 2 orgs with separate admins ─────────────────

  test('08-02: cross-org isolation — org1 admin sees org1 users, org2 admin sees org2 users', async ({ page: page1 }, testInfo) => {
    // === org1 admin のセッション ===
    await bypassLogin(page1, { role: 'admin', email: 'admin@org1.example.com', orgId: 1 });
    await mockUsersEndpoint(page1, USER_LIST_WITH_ORG);
    await gotoAdmin(page1, '/users');

    // org1 admin は acme (org1) メンバーを見る
    await expect(page1.locator('text=admin@acme.example.com').first()).toBeVisible({ timeout: 6000 });
    await expect(page1.locator('text=editor@acme.example.com').first()).toBeVisible({ timeout: 6000 });

    // org2 メンバーは表示されない
    const org2email = page1.locator('text=admin@globex.example.com');
    await expect(org2email).not.toBeVisible();
  });

  test('08-02b: cross-org isolation — org2 admin sees only org2 users', async ({ page }) => {
    await bypassLogin(page, { role: 'admin', email: 'admin@globex.example.com', orgId: 2 });
    await mockUsersEndpoint(page, USER_LIST_ORG2);
    await gotoAdmin(page, '/users');

    // org2 admin は globex (org2) メンバーのみ見る
    await expect(page.locator('text=admin@globex.example.com').first()).toBeVisible({ timeout: 6000 });

    // org1 メンバーは表示されない
    const org1email = page.locator('text=admin@acme.example.com');
    await expect(org1email).not.toBeVisible();
  });

  // ── 08-03: superadmin oversight — can view all orgs and all users ─────────────

  test('08-03: superadmin oversight — sees all orgs in org management', async ({ page }) => {
    await bypassLogin(page, { role: 'superadmin' });
    await mockOrganizationsEndpoint(page, ORGANIZATION_LIST);
    await gotoSuperadmin(page, '/organizations');

    // superadmin は全 org を見られる
    await expect(page.locator('text=Acme Corp')).toBeVisible({ timeout: 6000 });
    await expect(page.locator('text=Globex Inc')).toBeVisible({ timeout: 6000 });
  });

  test('08-03b: superadmin oversight — can access all users (not org-filtered)', async ({ page }) => {
    await bypassLogin(page, { role: 'superadmin' });
    // superadmin は全ユーザー一覧（org フィルタなし）
    await mockUsersEndpoint(page, USER_LIST);
    await gotoAdmin(page, '/users');

    // 全ユーザーが表示される
    await expect(page.locator('text=admin@example.com').first()).toBeVisible({ timeout: 6000 });
    await expect(page.locator('text=editor@example.com').first()).toBeVisible({ timeout: 6000 });
  });

  // ── 08-04: role-based access control matrix ───────────────────────────────────

  test('08-04: access matrix — admin can manage users, superadmin can manage orgs, editor can do neither', async ({ page }) => {
    // === editor → users: 拒否 ===
    await bypassLogin(page, { role: 'editor', orgId: 1 });
    await gotoAdmin(page, '/users');
    await expect(page).toHaveURL(/\/forbidden/, { timeout: 6000 });

    // === editor → superadmin orgs: 拒否 ===
    await gotoSuperadmin(page, '/organizations');
    await expect(page).toHaveURL(/\/forbidden/, { timeout: 6000 });

    // セッションをリセット

    await page.evaluate(() => { localStorage.clear(); });

    // === admin → users: 許可 ===
    await bypassLogin(page, { role: 'admin', orgId: 1 });
    await mockUsersEndpoint(page, USER_LIST_EMPTY);
    await gotoAdmin(page, '/users');
    await expect(page).not.toHaveURL(/\/forbidden/, { timeout: 6000 });

    // === admin → superadmin orgs: 拒否（admin は superadmin 専用エリアに入れない）===
    await gotoSuperadmin(page, '/organizations');
    await expect(page).toHaveURL(/\/forbidden/, { timeout: 6000 });
  });

  // ── 08-05: full superadmin org management lifecycle ───────────────────────────

  test('08-05: superadmin lifecycle — list orgs, view detail, navigate back', async ({ page }) => {
    await bypassLogin(page, { role: 'superadmin' });

    // Org 一覧モック
    await mockOrganizationsEndpoint(page, ORGANIZATION_LIST);
    // Org 詳細モック
    await page.route('**/api/v1/organizations/1', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(ORGANIZATION_DETAIL) });
      }
      return route.continue();
    });

    // Step 1: org 一覧
    await gotoSuperadmin(page, '/organizations');
    await expect(page.locator('text=Acme Corp')).toBeVisible({ timeout: 6000 });
    await expect(page.locator('text=Globex Inc')).toBeVisible({ timeout: 6000 });

    // Step 2: org 詳細へ（org名は td テキストのみ — Edit リンクをクリック）
    await page.locator('a[href*="/organizations/1"]:has-text("Edit")').first().click();
    await expect(page.locator('h1')).toContainText('Acme Corp', { timeout: 6000 });
    await expect(page.locator('text=Organization Settings')).toBeVisible({ timeout: 6000 });
    await expect(page.locator('button:has-text("Delete Organization")')).toBeVisible({ timeout: 6000 });

    // Step 3: 戻るリンクで一覧へ
    await page.locator('main a:has-text("Organizations")').click();
    await expect(page).toHaveURL(`${BASE_URL}/superadmin/organizations`);
    await expect(page.locator('text=Acme Corp')).toBeVisible({ timeout: 6000 });
  });
});
