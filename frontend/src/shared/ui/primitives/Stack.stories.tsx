import type { Meta, StoryObj } from '@storybook/react'
import { Button } from './Button'
import { Stack } from './Stack'
import { Text } from './Text'

/**
 * Stack — layout primitive for spacing children.
 *
 * In:  direction, gap, children
 * Out: (none — presentational)
 *
 * Does not: manage scroll, grid columns, or responsive breakpoints beyond flex.
 */
const meta = {
  title: 'Primitives/Stack',
  component: Stack,
  args: {
    children: (
      <>
        <Text variant="heading-sm">Entity Types</Text>
        <Text muted>Manage schemas for your records.</Text>
        <Button>Create type</Button>
      </>
    ),
  },
} satisfies Meta<typeof Stack>

export default meta
type Story = StoryObj<typeof meta>

export const Vertical: Story = {}

export const Horizontal: Story = {
  args: {
    direction: 'horizontal',
    gap: 'sm',
    children: (
      <>
        <Button variant="secondary">Cancel</Button>
        <Button>Save</Button>
      </>
    ),
  },
}

export const TightGap: Story = {
  args: { gap: 'xs' },
}
