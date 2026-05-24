import { Link } from 'react-router-dom'
import { Stack, Text } from '@/shared/ui'

export function HomePage() {
  return (
    <Stack gap="md">
      <Text as="h1" variant="heading-md">
        Admin dashboard
      </Text>
      <Text muted>
        Phase 4 scaffold is running. Use Entity types to verify API integration via TanStack Query.
      </Text>
      <Link to="/view" className="text-body font-medium text-accent hover:text-accent-hover">
        Open public site →
      </Link>
    </Stack>
  )
}
