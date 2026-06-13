import { createBrowserRouter, RouterProvider } from 'react-router-dom'
import { AcceptInvitePage } from '@/pages/accept-invite/AcceptInvitePage'
import { PublicBrowsePage } from '@/pages/consumer/PublicBrowsePage'
import { PublicIndexPage } from '@/pages/consumer/PublicIndexPage'
import { PublicRecordDetailPage } from '@/pages/consumer/PublicRecordDetailPage'
import { PublicSearchPage } from '@/pages/consumer/PublicSearchPage'
import { PublicShell } from '@/pages/consumer/PublicShell'
import { PublicTagArchivePage } from '@/pages/consumer/PublicTagArchivePage'
import { EntityRecordPage } from '@/pages/entity-record/EntityRecordPage'
import { EntityRecordsPage } from '@/pages/entity-records/EntityRecordsPage'
import { EntityTypesPage } from '@/pages/entity-types/EntityTypesPage'
import { FieldDefsPage } from '@/pages/field-defs/FieldDefsPage'
import { ForbiddenPage } from '@/pages/forbidden/ForbiddenPage'
import { HomePage } from '@/pages/home/HomePage'
import { AppShell } from '@/pages/layout/AppShell'
import { LoginPage } from '@/pages/login/LoginPage'
import { MediaPage } from '@/pages/media/MediaPage'
import { NavigationPage } from '@/pages/navigation/NavigationPage'
import { WidgetsPage } from '@/pages/widgets/WidgetsPage'
import { NotFoundPage } from '@/pages/not-found/NotFoundPage'
import { ResetPasswordPage } from '@/pages/reset-password/ResetPasswordPage'
import { SiteSettingsPage } from '@/pages/settings/SiteSettingsPage'
import { VerifyEmailPage } from '@/pages/verify-email/VerifyEmailPage'
import { CommentsPage } from '@/pages/comments/CommentsPage'
import { UserEditPage } from '@/pages/users/UserEditPage'
import { UsersPage } from '@/pages/users/UsersPage'
import { WebhooksPage } from '@/pages/webhooks/WebhooksPage'
import { NotificationChannelsPage } from '@/pages/notifications/NotificationChannelsPage'
import { TagsPage } from '@/pages/tags/TagsPage'
import { SuperadminShell } from '@/pages/superadmin/SuperadminShell'
import { OrganizationsPage } from '@/pages/superadmin/OrganizationsPage'
import { OrganizationDetailPage } from '@/pages/superadmin/OrganizationDetailPage'
import { SettingsPage } from '@/pages/superadmin/SettingsPage'
import { DataMigrationPage } from '@/pages/superadmin/DataMigrationPage'
import { RequireAuth } from '@/shared/auth/RequireAuth'

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
      { path: 'navigation', element: <NavigationPage /> },
      { path: 'widgets', element: <WidgetsPage /> },
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
  return <RouterProvider router={router} />
}
