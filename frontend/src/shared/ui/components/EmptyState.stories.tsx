import type { Meta, StoryObj } from '@storybook/react'
import { Button } from '@/shared/ui/primitives/Button'
import { EmptyState } from './EmptyState'

/**
 * EmptyState — composed empty panel.
 *
 * In:  title, description, action
 * Out: (via action slot callbacks)
 *
 * Does not: fetch lists or navigate routes.
 */
const meta = {
  title: 'Components/EmptyState',
  component: EmptyState,
  args: {
    title: 'No entity types yet',
    description: 'Create your first entity type to start defining records.',
  },
} satisfies Meta<typeof EmptyState>

export default meta
type Story = StoryObj<typeof meta>

export const Default: Story = {}

export const WithAction: Story = {
  args: {
    action: <Button>Create entity type</Button>,
  },
}
