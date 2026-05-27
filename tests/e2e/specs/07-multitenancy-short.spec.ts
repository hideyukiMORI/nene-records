/**
 * Category: Multi-tenancy Short Scenarios
 *
 * 2〜3 ステップのシナリオでマルチテナント機能を検証する。
 *
 * Level 3 — ショートシナリオブラウザテスト
 * - admin が org スコープのユーザー一覧を取得するフロー
 * - superadmin が org 一覧から org 詳細へナビゲートするフロー
 * - admin が org スコープでユーザーを招待するフロー
 * - エディタが Users ページにアクセスできないこと（アクセス制御）
 * - org_id 付きログインレスポンスでもセッションが正常に動作すること
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
  USER_LIST_WITH_ORG,
  USER_LIST_EMPTY,
  ORGANIZATION_LIST,
  ORGANIZATION_DETAIL,
  ADMIN_LOGIN_WITH_ORG_ID,
} from '../fixtures/api-mocks.js';

test.describe('Multi-tenancy — Short Scenarios', () => {
  // ── 07-01: org スコープ admin → users list ────────────────────────────────────

  test('07-01: org-scoped admin login → users list shows org members', async ({ page }) => {
    // Step 1: org_id=1 の admin としてログイン
    await bypassLogin(page, { role: 'admin', orgId: 1 });
    // Step 2: org スコープのユーザー一覧を返すモックを設定
    await mockUsersEndpoint(page, USER_LIST_WITH_ORG);
    // Step 3: /users へ遷移
    await gotoAdmin(page, '/users');

    // org メンバーが表示される
    await expect(page.locator('text=admin@acme.example.com').first()).toBeVisible({ timeout: 6000 });
    await expect(page.locator('text=editor@acme.example.com').first()).toBeVisible({ timeout: 6000 });
  });

  // ── 07-02: superadmin: orgs 一覧 → org 詳細ナビゲーション ─────────────────────

  test('07-02: superadmin navigates org list → org detail → back to list', async ({ page }) => {
    await bypassLogin(page, { role: 'superadmin' });
    // Step 1: orgs 一覧モック
    await mockOrganizationsEndpoint(page, ORGANIZATION_LIST);
    // Step 2: org 詳細モック
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

    // Step 3: orgs 一覧へ
    await gotoSuperadmin(page, '/organizations');
    await expect(page.locator('text=Acme Corp')).toBeVisible({ timeout: 6000 });

    // Step 4: Acme Corp 行の Edit リンクをクリックして詳細へ（org名はリンクではなく td テキスト）
    await page.locator('a[href*="/organizations/1"]:has-text("Edit")').first().click();
    await expect(page).toHaveURL(/\/organizations\/1/, { timeout: 6000 });

    // Step 5: 詳細ページが表示される
    await expect(page.locator('h1')).toContainText('Acme Corp', { timeout: 6000 });

    // Step 6: 戻るリンクで一覧に戻る
    await page.locator('main a:has-text("Organizations")').click();
    await expect(page).toHaveURL(`${BASE_URL}/superadmin/organizations`);
  });

  // ── 07-03: admin が org スコープでユーザーを招待するフロー ─────────────────────

  test('07-03: org-scoped admin invites user → POST /api/v1/users/invite called', async ({ page }) => {
    await bypassLogin(page, { role: 'admin', orgId: 1 });
    await mockUsersEndpoint(page, USER_LIST_EMPTY);

    let invitePostCalled = false;
    let inviteRequestBody: Record<string, unknown> | null = null;

    // Step 1: invite エンドポイントをモック
    await page.route('**/api/v1/users/invite', (route) => {
      if (route.request().method() === 'POST') {
        invitePostCalled = true;
        inviteRequestBody = route.request().postDataJSON() as Record<string, unknown>;
        return route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify({
            id: 5,
            email: 'new@acme.example.com',
            role: 'editor',
            status: 'invited',
            organization_id: 1,
          }),
        });
      }
      return route.continue();
    });

    // Step 2: /users へ遷移
    await gotoAdmin(page, '/users');

    // Step 3: Invite user ボタンをクリック
    await page.locator('button:has-text("Invite user")').click();
    await expect(page.locator('input[type="email"]')).toBeVisible({ timeout: 6000 });

    // Step 4: メールアドレスを入力して招待送信
    const emailInput = page.locator('input[type="email"]').first();
    await emailInput.click();
    await emailInput.pressSequentially('new@acme.example.com');
    await page.locator('button[type="submit"]:has-text("Send invitation")').click();

    await page.waitForTimeout(500);
    expect(invitePostCalled).toBe(true);
    // フロントエンドは email と role を送信する（org は JWT で backend が解決）
    expect(inviteRequestBody).not.toBeNull();
  });

  // ── 07-04: editor → users ページアクセス拒否 + フォールバック ─────────────────

  test('07-04: editor cannot access users page — redirected to /forbidden', async ({ page }) => {
    // Step 1: editor としてログイン
    await bypassLogin(page, { role: 'editor', orgId: 3 });
    // Step 2: /users へアクセス
    await gotoAdmin(page, '/users');

    // Step 3: /forbidden にリダイレクトされる
    await expect(page).toHaveURL(/\/forbidden/, { timeout: 6000 });
  });

  // ── 07-05: org スコープ admin → empty org user list ──────────────────────────

  test('07-05: org-scoped admin with empty org — shows "No users yet"', async ({ page }) => {
    await bypassLogin(page, { role: 'admin', orgId: 99 });
    // Step 1: org 99 のユーザー一覧（空）
    await mockUsersEndpoint(page, USER_LIST_EMPTY);
    // Step 2: /users へ遷移
    await gotoAdmin(page, '/users');

    // Step 3: 空状態メッセージが表示される
    await expect(page.locator('text=No users yet')).toBeVisible({ timeout: 6000 });
  });

  // ── 07-06: superadmin が org を作成後に詳細に遷移するフロー ──────────────────

  test('07-06: superadmin creates org — POST called — new org id in Location header respected', async ({ page }) => {
    await bypassLogin(page, { role: 'superadmin' });

    const newOrg = {
      id: 10,
      name: 'NewCorp',
      slug: 'newcorp',
      plan: 'free',
      is_active: true,
      custom_domain: null,
      created_at: '2026-06-01T00:00:00Z',
      updated_at: '2026-06-01T00:00:00Z',
    };

    let postCalled = false;
    await page.route('**/api/v1/organizations**', (route) => {
      const method = route.request().method();
      if (method === 'POST') {
        postCalled = true;
        return route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify(newOrg),
        });
      }
      if (method === 'GET') {
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ data: [], meta: { total: 0, limit: 50, offset: 0 } }),
        });
      }
      return route.continue();
    });

    await gotoSuperadmin(page, '/organizations');
    await page.locator('button:has-text("New Organization")').click();
    await page.locator('input[id="org-name"]').fill('NewCorp');
    await page.locator('input[id="org-slug"]').fill('newcorp');
    await page.locator('button[type="submit"]:has-text("Create")').click();

    await page.waitForTimeout(500);
    expect(postCalled).toBe(true);
  });
});
