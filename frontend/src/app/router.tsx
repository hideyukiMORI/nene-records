import { createBrowserRouter, RouterProvider } from 'react-router-dom'
import { PublicBrowsePage } from '@/pages/consumer/PublicBrowsePage'
import { PublicIndexPage } from '@/pages/consumer/PublicIndexPage'
import { PublicRecordDetailPage } from '@/pages/consumer/PublicRecordDetailPage'
import { PublicShell } from '@/pages/consumer/PublicShell'
import { EntityRecordPage } from '@/pages/entity-record/EntityRecordPage'
import { EntityRecordsPage } from '@/pages/entity-records/EntityRecordsPage'
import { EntityTypesPage } from '@/pages/entity-types/EntityTypesPage'
import { FieldDefsPage } from '@/pages/field-defs/FieldDefsPage'
import { ForbiddenPage } from '@/pages/forbidden/ForbiddenPage'
import { HomePage } from '@/pages/home/HomePage'
import { AppShell } from '@/pages/layout/AppShell'
import { LoginPage } from '@/pages/login/LoginPage'
import { NavigationPage } from '@/pages/navigation/NavigationPage'
import { NotFoundPage } from '@/pages/not-found/NotFoundPage'
import { SiteSettingsPage } from '@/pages/settings/SiteSettingsPage'
import { WebhooksPage } from '@/pages/webhooks/WebhooksPage'
import { TagsPage } from '@/pages/tags/TagsPage'
import { RequireAuth } from '@/shared/auth/RequireAuth'

function AdminShell() {
  return (
    <RequireAuth>
      <AppShell />
    </RequireAuth>
  )
}

const router = createBrowserRouter([
  {
    path: '/login',
    element: <LoginPage />,
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
      { path: 'navigation', element: <NavigationPage /> },
      { path: 'webhooks', element: <WebhooksPage /> },
      { path: 'settings', element: <SiteSettingsPage /> },
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
    path: '/',
    element: <PublicShell />,
    errorElement: <NotFoundPage />,
    children: [
      { index: true, element: <PublicIndexPage /> },
      { path: ':entityTypeSlug', element: <PublicBrowsePage /> },
      { path: ':entityTypeSlug/:entityId', element: <PublicRecordDetailPage /> },
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
