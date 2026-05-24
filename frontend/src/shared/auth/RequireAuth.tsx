import { Navigate, useLocation } from 'react-router-dom'
import { authStore } from '@/entities/auth'

interface Props {
  children: React.ReactNode
}

export function RequireAuth({ children }: Props) {
  const location = useLocation()

  if (!authStore.isAuthenticated()) {
    return <Navigate to="/login" state={{ from: location }} replace />
  }

  return <>{children}</>
}
