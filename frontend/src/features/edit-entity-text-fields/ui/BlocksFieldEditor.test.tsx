import { useState } from 'react'
import { afterEach, describe, expect, it } from 'vitest'
import { cleanup, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { BlocksFieldEditor } from './BlocksFieldEditor'

afterEach(cleanup)

function Harness({ initial = '' }: { initial?: string }) {
  const [value, setValue] = useState(initial)
  return (
    <BlocksFieldEditor
      id="f"
      label="body (blocks)"
      value={value}
      disabled={false}
      onChange={setValue}
    />
  )
}

describe('BlocksFieldEditor', () => {
  it('shows the empty state when there are no blocks', () => {
    renderWithProviders(<Harness />)
    expect(screen.getByText('No blocks yet')).toBeInTheDocument()
  })

  it('adds a text block and opens its inspector', async () => {
    const user = userEvent.setup()
    renderWithProviders(<Harness />)

    await user.click(screen.getByRole('button', { name: 'Add Text' }))

    expect(screen.queryByText('No blocks yet')).not.toBeInTheDocument()
    // The inspector for the new block exposes a Markdown body editor.
    expect(screen.getByText('Body (Markdown)')).toBeInTheDocument()
  })

  it('renders existing blocks parsed from the value', () => {
    const doc = JSON.stringify([
      { id: 'b1', type: 'callout', data: { kind: 'ok', title: 'Nice', body: 'Done' } },
    ])
    renderWithProviders(<Harness initial={doc} />)

    expect(screen.getByText('Callout')).toBeInTheDocument()
  })
})
