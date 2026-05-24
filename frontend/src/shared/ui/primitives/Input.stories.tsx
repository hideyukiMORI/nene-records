import type { Meta, StoryObj } from '@storybook/react'
import { fn } from 'storybook/test'
import { Input } from './Input'

/**
 * Input — labeled text field primitive.
 *
 * In:  id, label, type, value, disabled, error
 * Out: onChange(event), onBlur(event)
 *
 * Does not: validate business rules or submit forms.
 */
const meta = {
  title: 'Primitives/Input',
  component: Input,
  args: {
    id: 'entity-name',
    label: 'Name',
    value: 'Article',
    onChange: fn(),
  },
} satisfies Meta<typeof Input>

export default meta
type Story = StoryObj<typeof meta>

export const Default: Story = {}

export const WithError: Story = {
  args: {
    error: 'Name is required',
    value: '',
  },
}

export const Disabled: Story = {
  args: { disabled: true },
}
