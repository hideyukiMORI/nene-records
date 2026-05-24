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
    </Stack>
  )
}
