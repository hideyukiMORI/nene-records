/**
 * Admin E2E test helpers.
 *
 * Provides mock setup and common navigation helpers for Admin SPA tests.
 * All API calls are intercepted by page.route() — no real backend needed.
 *
 * Key patterns:
 *  - mockAuth()          sets up login endpoint + default entity-type list (pinned)
 *  - bypassLogin()       injects token directly into localStorage (faster)
 *  - mockDashboard()     stubs GET /api/v1/dashboard
 *  - mockEntityTypes()   stubs GET /api/v1/entity-types
 *  - mockTags()          stubs GET /api/v1/tags
 *  - mockUsers()         stubs GET /api/v1/users
 *  - mockOrganizations() stubs GET /api/v1/organizations
 */

import type { Page } from "@playwright/test";
import {
  ADMIN_TOKEN,
  DEFAULT_LOGIN_RESPONSE,
  ENTITY_TYPE_LIST_EMPTY,
  DASHBOARD_EMPTY,
} from "./api-mocks.js";

// ── Constants ─────────────────────────────────────────────────────────────────

export const BASE_URL = "http://localhost:4173";
export const LOGIN_URL = "/login";
export const ADMIN_URL = "/admin";
export const SUPERADMIN_URL = "/superadmin";
/** localStorage key used by authStore (non-secret profile; token is an HttpOnly cookie) */
export const AUTH_STORAGE_KEY = "nene_records_session";

// ── Auth helpers ──────────────────────────────────────────────────────────────

/**
 * Mock POST /api/v1/auth/login with a successful response.
 */
export async function mockLoginEndpoint(
  page: Page,
  response: object = DEFAULT_LOGIN_RESPONSE,
): Promise<void> {
  await page.route("**/api/v1/auth/login", (route) => {
    if (route.request().method() === "POST") {
      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify(response),
      });
    }
    return route.continue();
  });
}

/**
 * Inject auth token directly into localStorage — faster than going through the login form.
 * Also mocks the entity-type list (needed by AppShell sidebar) and sets up minimal defaults.
 */
export async function bypassLogin(
  page: Page,
  options: {
    email?: string;
    role?: string;
    orgId?: number | null;
    entityTypes?: object;
  } = {},
): Promise<void> {
  // Non-secret profile only; the real token would be an HttpOnly cookie. E2E
  // specs mock the API, so no cookie is needed to satisfy authStore.
  const session: Record<string, unknown> = {
    expiresAt: "2099-01-01T00:00:00Z",
    email: options.email ?? "admin@example.com",
    role: options.role ?? "admin",
  };

  // org_id を明示的に指定した場合のみセッションに含める（multi-tenancy テスト用）
  if ("orgId" in options) {
    session["orgId"] = options.orgId ?? null;
  }

  // Mock entity-types list (used by sidebar in AppShell)
  await mockEntityTypesEndpoint(
    page,
    options.entityTypes ?? ENTITY_TYPE_LIST_EMPTY,
  );

  // Navigate to set localStorage on the right origin
  await page.goto(BASE_URL);
  await page.evaluate(
    ([key, value]) => {
      localStorage.setItem(key, value);
    },
    [AUTH_STORAGE_KEY, JSON.stringify(session)] as [string, string],
  );
}

/**
 * Navigate to the admin panel with auth already injected.
 */
export async function gotoAdmin(page: Page, path = ""): Promise<void> {
  await page.goto(`${ADMIN_URL}${path}`);
  // Wait for the page to settle (React renders)
  await page.waitForLoadState("networkidle");
}

/**
 * Navigate to the superadmin panel with auth already injected.
 */
export async function gotoSuperadmin(page: Page, path = ""): Promise<void> {
  await page.goto(`${SUPERADMIN_URL}${path}`);
  await page.waitForLoadState("networkidle");
}

/**
 * Full login flow: navigate to /login, fill credentials, submit.
 */
export async function loginAs(
  page: Page,
  email = "admin@example.com",
  password = "password",
): Promise<void> {
  await page.goto(LOGIN_URL);
  await page.locator('input[type="email"]').fill(email);
  await page.locator('input[type="password"]').fill(password);
  await page.locator('button[type="submit"]').click();
  // Wait for redirect to /admin
  await page.waitForURL(`${BASE_URL}/admin`);
}

// ── API mock helpers ───────────────────────────────────────────────────────────

export async function mockEntityTypesEndpoint(
  page: Page,
  response: object,
): Promise<void> {
  await page.route("**/api/v1/entity-types**", (route) => {
    if (route.request().method() === "GET") {
      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify(response),
      });
    }
    return route.continue();
  });
}

export async function mockDashboard(
  page: Page,
  response: object = DASHBOARD_EMPTY,
): Promise<void> {
  await page.route("**/api/v1/dashboard", (route) => {
    if (route.request().method() === "GET") {
      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify(response),
      });
    }
    return route.continue();
  });
}

export async function mockTagsEndpoint(
  page: Page,
  response: object,
): Promise<void> {
  await page.route("**/api/v1/tags**", (route) => {
    if (route.request().method() === "GET") {
      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify(response),
      });
    }
    return route.continue();
  });
}

export async function mockUsersEndpoint(
  page: Page,
  response: object,
): Promise<void> {
  await page.route("**/api/v1/users**", (route) => {
    if (route.request().method() === "GET") {
      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify(response),
      });
    }
    return route.continue();
  });
}

export async function mockOrganizationsEndpoint(
  page: Page,
  response: object,
): Promise<void> {
  await page.route("**/api/v1/organizations**", (route) => {
    if (route.request().method() === "GET") {
      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify(response),
      });
    }
    return route.continue();
  });
}
