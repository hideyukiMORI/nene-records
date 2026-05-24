import type { Meta, StoryObj } from '@storybook/react'
import { fn } from 'storybook/test'
import { ConfirmDialog } from './ConfirmDialog'

/**
 * ConfirmDialog — confirmation overlay.
 *
 * In:  open, title, description, confirmLabel, cancelLabel, isPending
 * Out: onConfirm(), onCancel()
 *
 * Does not: call API or manage entity state.
 */
const meta = {
  title: 'Components/ConfirmDialog',
  component: ConfirmDialog,
  args: {
    open: true,
    title: 'Delete entity type?',
    description: 'This action cannot be undone.',
    onConfirm: fn(),
    onCancel: fn(),
  },
} satisfies Meta<typeof ConfirmDialog>

export default meta
type Story = StoryObj<typeof meta>

export const Default: Story = {}

export const Pending: Story = {
  args: { isPending: true, confirmLabel: 'Deleting…' },
}

export const Closed: Story = {
  args: { open: false },
}
