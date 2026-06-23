import type { Meta, StoryObj } from '@storybook/react'
import { fn } from 'storybook/test'
import { Modal } from './Modal'
import { Stack } from '../primitives/Stack'
import { Text } from '../primitives/Text'

/**
 * Modal — overlay + centered dialog panel.
 *
 * In:  onClose, closeLabel, labelledBy, panelClassName, className, children
 * Out: onClose() (backdrop click / Escape)
 *
 * Does not: own open state, fetch data, or know entity ids.
 */
const meta = {
  title: 'Components/Modal',
  component: Modal,
  args: {
    onClose: fn(),
    closeLabel: 'Close dialog',
    labelledBy: 'modal-story-title',
    panelClassName: 'max-w-md shadow-md',
    children: (
      <Stack gap="xs">
        <Text as="h2" id="modal-story-title" variant="heading-sm">
          Dialog title
        </Text>
        <Text muted>Backdrop click or Escape calls onClose.</Text>
      </Stack>
    ),
  },
} satisfies Meta<typeof Modal>

export default meta
type Story = StoryObj<typeof meta>

export const Default: Story = {}

export const Wide: Story = {
  args: { panelClassName: 'max-w-2xl shadow-md' },
}
