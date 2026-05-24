import { createBrowserRouter, RouterProvider } from 'react-router-dom'
import { EntityTypesPage } from '@/pages/entity-types/EntityTypesPage'
import { HomePage } from '@/pages/home/HomePage'
import { AppShell } from '@/pages/layout/AppShell'

const router = createBrowserRouter([
  {
    path: '/',
    element: <AppShell />,
    children: [
      { index: true, element: <HomePage /> },
      { path: 'entity-types', element: <EntityTypesPage /> },
    ],
  },
])

export function AppRouter() {
  return <RouterProvider router={router} />
}
