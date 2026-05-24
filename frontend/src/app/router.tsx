import { createBrowserRouter, RouterProvider } from 'react-router-dom'
import { PublicBrowsePage } from '@/pages/consumer/PublicBrowsePage'
import { PublicRecordDetailPage } from '@/pages/consumer/PublicRecordDetailPage'
import { PublicShell } from '@/pages/consumer/PublicShell'
import { EntityRecordPage } from '@/pages/entity-record/EntityRecordPage'
import { EntityRecordsPage } from '@/pages/entity-records/EntityRecordsPage'
import { EntityTypesPage } from '@/pages/entity-types/EntityTypesPage'
import { FieldDefsPage } from '@/pages/field-defs/FieldDefsPage'
import { HomePage } from '@/pages/home/HomePage'
import { AppShell } from '@/pages/layout/AppShell'
import { TagsPage } from '@/pages/tags/TagsPage'

const router = createBrowserRouter([
  {
    path: '/',
    element: <AppShell />,
    children: [
      { index: true, element: <HomePage /> },
      { path: 'entity-types', element: <EntityTypesPage /> },
      { path: 'tags', element: <TagsPage /> },
      { path: 'entity-types/:entityTypeId/fields', element: <FieldDefsPage /> },
      { path: 'entity-types/:entityTypeId/entities', element: <EntityRecordsPage /> },
      { path: 'entity-types/:entityTypeId/entities/:entityId', element: <EntityRecordPage /> },
    ],
  },
  {
    path: '/view',
    element: <PublicShell />,
    children: [
      { path: ':entityTypeSlug', element: <PublicBrowsePage /> },
      { path: ':entityTypeSlug/:entityId', element: <PublicRecordDetailPage /> },
    ],
  },
])

export function AppRouter() {
  return <RouterProvider router={router} />
}
