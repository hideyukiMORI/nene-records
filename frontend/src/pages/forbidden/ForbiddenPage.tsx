import { Link } from 'react-router-dom'
import { Button, Stack, Text } from '@/shared/ui'

export function ForbiddenPage() {
  return (
    <Stack gap="md" className="py-stack-xl">
      <Text as="h1" variant="heading-md">
        Access denied
      </Text>
      <Text muted>
        You are signed in, but your account does not have permission to perform this action.
      </Text>
      <Stack direction="horizontal" gap="sm">
        <Link to="/">
          <Button variant="secondary">Back to home</Button>
        </Link>
      </Stack>
    </Stack>
  )
}
