import { Component, type ErrorInfo, type ReactNode } from 'react'
import { Button, Stack, Text } from '@/shared/ui'

interface RootErrorBoundaryProps {
  children: ReactNode
}

interface RootErrorBoundaryState {
  hasError: boolean
}

export class RootErrorBoundary extends Component<RootErrorBoundaryProps, RootErrorBoundaryState> {
  override state: RootErrorBoundaryState = { hasError: false }

  static getDerivedStateFromError(): RootErrorBoundaryState {
    return { hasError: true }
  }

  override componentDidCatch(error: Error, info: ErrorInfo): void {
    if (import.meta.env.DEV) {
      console.error('Root error boundary caught:', error, info)
    }
  }

  private handleReset = (): void => {
    this.setState({ hasError: false })
    window.location.assign('/')
  }

  override render(): ReactNode {
    if (this.state.hasError) {
      return (
        <main className="mx-auto flex min-h-screen max-w-3xl items-center px-inline-md py-stack-xl">
          <Stack gap="md">
            <Text as="h1" variant="heading-md">
              Something went wrong
            </Text>
            <Text muted>An unexpected error occurred in the admin UI.</Text>
            <Button variant="secondary" onClick={this.handleReset}>
              Return home
            </Button>
          </Stack>
        </main>
      )
    }

    return this.props.children
  }
}
