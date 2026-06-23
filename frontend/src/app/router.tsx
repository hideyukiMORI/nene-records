import { lazy, Suspense, type ComponentType } from 'react'
import { createBrowserRouter, Navigate, RouterProvider } from 'react-router-dom'
// Eager: route frame + entry/error pages (needed immediately; the shells render
// the Suspense fallback while a lazy content page chunk loads).
import { PublicShell } from '@/pages/consumer/PublicShell'
import { ForbiddenPage } from '@/pages/forbidden/ForbiddenPage'
import { AppShell } from '@/pages/layout/AppShell'
import { LoginPage } from '@/pages/login/LoginPage'
import { NotFoundPage } from '@/pages/not-found/NotFoundPage'
import { SuperadminShell } from '@/pages/superadmin/SuperadminShell'
import { RequireAuth } from '@/shared/auth/RequireAuth'

// Lazy: per-route code splitting for the content pages (named exports → default).
const named = <T,>(loader: () => Promise<Record<string, T>>, name: string) =>
  lazy(() => loader().then((m) => ({ default: m[name] as ComponentType })))

const AcceptInvitePage = named(() => import('@/pages/accept-invite/AcceptInvitePage'), 'AcceptInvitePage') // prettier-ignore
const ResetPasswordPage = named(() => import('@/pages/reset-password/ResetPasswordPage'), 'ResetPasswordPage') // prettier-ignore
const VerifyEmailPage = named(() => import('@/pages/verify-email/VerifyEmailPage'), 'VerifyEmailPage') // prettier-ignore
const HomePage = named(() => import('@/pages/home/HomePage'), 'HomePage')
const EntityTypesPage = named(() => import('@/pages/entity-types/EntityTypesPage'), 'EntityTypesPage') // prettier-ignore
const TagsPage = named(() => import('@/pages/tags/TagsPage'), 'TagsPage')
const CommentsPage = named(() => import('@/pages/comments/CommentsPage'), 'CommentsPage')
const AppearanceLayoutPage = named(() => import('@/pages/appearance/AppearanceLayoutPage'), 'AppearanceLayoutPage') // prettier-ignore
const MediaPage = named(() => import('@/pages/media/MediaPage'), 'MediaPage')
const WebhooksPage = named(() => import('@/pages/webhooks/WebhooksPage'), 'WebhooksPage')
const NotificationChannelsPage = named(() => import('@/pages/notifications/NotificationChannelsPage'), 'NotificationChannelsPage') // prettier-ignore
const SiteSettingsPage = named(() => import('@/pages/settings/SiteSettingsPage'), 'SiteSettingsPage') // prettier-ignore
const UsersPage = named(() => import('@/pages/users/UsersPage'), 'UsersPage')
const UserEditPage = named(() => import('@/pages/users/UserEditPage'), 'UserEditPage')
const FieldDefsPage = named(() => import('@/pages/field-defs/FieldDefsPage'), 'FieldDefsPage')
const EntityRecordsPage = named(() => import('@/pages/entity-records/EntityRecordsPage'), 'EntityRecordsPage') // prettier-ignore
const EntityRecordPage = named(() => import('@/pages/entity-record/EntityRecordPage'), 'EntityRecordPage') // prettier-ignore
const OrganizationsPage = named(() => import('@/pages/superadmin/OrganizationsPage'), 'OrganizationsPage') // prettier-ignore
const OrganizationDetailPage = named(() => import('@/pages/superadmin/OrganizationDetailPage'), 'OrganizationDetailPage') // prettier-ignore
const SettingsPage = named(() => import('@/pages/superadmin/SettingsPage'), 'SettingsPage')
const DataMigrationPage = named(() => import('@/pages/superadmin/DataMigrationPage'), 'DataMigrationPage') // prettier-ignore
const PublicIndexPage = named(() => import('@/pages/consumer/PublicIndexPage'), 'PublicIndexPage') // prettier-ignore
const PublicSearchPage = named(() => import('@/pages/consumer/PublicSearchPage'), 'PublicSearchPage') // prettier-ignore
const PublicTagArchivePage = named(() => import('@/pages/consumer/PublicTagArchivePage'), 'PublicTagArchivePage') // prettier-ignore
const PublicDateArchivePage = named(() => import('@/pages/consumer/PublicDateArchivePage'), 'PublicDateArchivePage') // prettier-ignore
const PublicBrowsePage = named(() => import('@/pages/consumer/PublicBrowsePage'), 'PublicBrowsePage') // prettier-ignore
const PublicRecordDetailPage = named(() => import('@/pages/consumer/PublicRecordDetailPage'), 'PublicRecordDetailPage') // prettier-ignore

function AdminShell() {
  return (
    <RequireAuth>
      <AppShell />
    </RequireAuth>
  )
}

function SuperadminGuard() {
  return (
    <RequireAuth>
      <SuperadminShell />
    </RequireAuth>
  )
}

const router = createBrowserRouter([
  {
    path: '/login',
    element: <LoginPage />,
  },
  {
    path: '/admin/accept-invite',
    element: <AcceptInvitePage />,
  },
  {
    path: '/admin/reset-password',
    element: <ResetPasswordPage />,
  },
  {
    path: '/admin/verify-email',
    element: <VerifyEmailPage />,
  },
  {
    path: '/forbidden',
    element: (
      <RequireAuth>
        <ForbiddenPage />
      </RequireAuth>
    ),
  },
  {
    path: '/admin',
    element: <AdminShell />,
    errorElement: <NotFoundPage />,
    children: [
      { index: true, element: <HomePage /> },
      { path: 'entity-types', element: <EntityTypesPage /> },
      { path: 'tags', element: <TagsPage /> },
      { path: 'comments', element: <CommentsPage /> },
      // Appearance › Layout builder (tabs: layout / menus). Old routes redirect.
      { path: 'appearance', element: <Navigate to="/admin/appearance/layout" replace /> },
      { path: 'appearance/:tab', element: <AppearanceLayoutPage /> },
      { path: 'navigation', element: <Navigate to="/admin/appearance/menus" replace /> },
      { path: 'widgets', element: <Navigate to="/admin/appearance/layout" replace /> },
      { path: 'media', element: <MediaPage /> },
      { path: 'webhooks', element: <WebhooksPage /> },
      { path: 'notifications', element: <NotificationChannelsPage /> },
      { path: 'settings', element: <SiteSettingsPage /> },
      { path: 'users', element: <UsersPage /> },
      { path: 'users/:id', element: <UserEditPage /> },
      { path: 'entity-types/:entityTypeSlug/fields', element: <FieldDefsPage /> },
      // Legacy long-form routes kept for schema management links
      { path: 'entity-types/:entityTypeSlug/entities', element: <EntityRecordsPage /> },
      { path: 'entity-types/:entityTypeSlug/entities/:entityId', element: <EntityRecordPage /> },
      // Short-form catch-all: /admin/:slug and /admin/:slug/:entityId
      // Must be last — specific routes above take priority
      { path: ':entityTypeSlug', element: <EntityRecordsPage /> },
      { path: ':entityTypeSlug/:entityId', element: <EntityRecordPage /> },
    ],
  },
  {
    path: '/superadmin',
    element: <SuperadminGuard />,
    errorElement: <NotFoundPage />,
    children: [
      { index: true, element: <OrganizationsPage /> },
      { path: 'organizations', element: <OrganizationsPage /> },
      { path: 'organizations/:id', element: <OrganizationDetailPage /> },
      { path: 'data-migration', element: <DataMigrationPage /> },
      { path: 'settings', element: <SettingsPage /> },
    ],
  },
  {
    path: '/',
    element: <PublicShell />,
    errorElement: <NotFoundPage />,
    children: [
      { index: true, element: <PublicIndexPage /> },
      // Static routes before `:entityTypeSlug` so they are not treated as types.
      { path: 'search', element: <PublicSearchPage /> },
      { path: 'tag/:tagSlug', element: <PublicTagArchivePage /> },
      { path: 'archive/:year/:month', element: <PublicDateArchivePage /> },
      { path: 'archive/:year/:month/:day', element: <PublicDateArchivePage /> },
      { path: ':entityTypeSlug', element: <PublicBrowsePage /> },
      // Wildcard captures any permalink pattern after the entity type slug
      // e.g. /posts/42, /posts/my-article, /posts/2024/01/my-article
      { path: ':entityTypeSlug/*', element: <PublicRecordDetailPage /> },
    ],
  },
  {
    path: '*',
    element: <NotFoundPage />,
  },
])

export function AppRouter() {
  // Top-level boundary for lazy routes that sit outside a shell (the auth pages);
  // shells provide their own Suspense so the nav stays visible during page load.
  return (
    <Suspense fallback={null}>
      <RouterProvider router={router} />
    </Suspense>
  )
}
