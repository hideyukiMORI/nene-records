import { createBrowserRouter, RouterProvider } from 'react-router-dom'
import { EntityRecordsPage } from '@/pages/entity-records/EntityRecordsPage'
import { EntityTypesPage } from '@/pages/entity-types/EntityTypesPage'
import { FieldDefsPage } from '@/pages/field-defs/FieldDefsPage'
import { HomePage } from '@/pages/home/HomePage'
import { AppShell } from '@/pages/layout/AppShell'

const router = createBrowserRouter([
  {
    path: '/',
    element: <AppShell />,
    children: [
      { index: true, element: <HomePage /> },
      { path: 'entity-types', element: <EntityTypesPage /> },
      { path: 'entity-types/:entityTypeId/fields', element: <FieldDefsPage /> },
      { path: 'entity-types/:entityTypeId/entities', element: <EntityRecordsPage /> },
    ],
  },
])

export function AppRouter() {
  return <RouterProvider router={router} />
}
