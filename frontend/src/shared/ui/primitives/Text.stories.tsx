import type { Meta, StoryObj } from '@storybook/react'
import { Text } from './Text'

/**
 * Text — typographic primitive.
 *
 * In:  as, variant, muted, children
 * Out: (none — presentational)
 *
 * Does not: fetch data or apply domain-specific copy rules.
 */
const meta = {
  title: 'Primitives/Text',
  component: Text,
  args: {
    children: 'Entity types define the schema for records.',
  },
} satisfies Meta<typeof Text>

export default meta
type Story = StoryObj<typeof meta>

export const Body: Story = {
  args: { variant: 'body' },
}

export const Caption: Story = {
  args: { variant: 'caption' },
}

export const HeadingSmall: Story = {
  args: { variant: 'heading-sm', as: 'h2' },
}

export const Muted: Story = {
  args: { muted: true },
}
