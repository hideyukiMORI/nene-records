import { afterEach, describe, expect, it, vi } from 'vitest'
import { cleanup, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { EntityTextFieldsForm } from './EntityTextFieldsForm'
import { type FieldDef, toFieldDefId } from '@/entities/field-def'
import { renderWithProviders } from '@tests/render/render-with-providers'

function textField(fieldKey: string, order: number): FieldDef {
  return {
    id: toFieldDefId(order),
    entityTypeId: 1,
    fieldKey,
    dataType: 'text',
    region: null,
    displayOrder: order,
  }
}

const FIELD_DEFS: FieldDef[] = [textField('title', 1), textField('subtitle', 2)]

afterEach(cleanup)

describe('EntityTextFieldsForm', () => {
  it('renders the server values into the fields', () => {
    renderWithProviders(
      <EntityTextFieldsForm
        fieldDefs={FIELD_DEFS}
        values={{ title: 'Hello', subtitle: 'World' }}
        isSubmitting={false}
        serverErrorTitle={null}
        onSubmit={vi.fn()}
      />,
    )

    expect(screen.getByLabelText('title (text)')).toHaveValue('Hello')
    expect(screen.getByLabelText('subtitle (text)')).toHaveValue('World')
  })

  it('submits the current edited values', async () => {
    const user = userEvent.setup()
    const onSubmit = vi.fn().mockResolvedValue(undefined)
    renderWithProviders(
      <EntityTextFieldsForm
        fieldDefs={FIELD_DEFS}
        values={{ title: 'Hello', subtitle: 'World' }}
        isSubmitting={false}
        serverErrorTitle={null}
        onSubmit={onSubmit}
      />,
    )

    const title = screen.getByLabelText('title (text)')
    await user.clear(title)
    await user.type(title, 'Edited')
    await user.click(screen.getByRole('button', { name: /save/i }))

    await waitFor(() => {
      expect(onSubmit).toHaveBeenCalledTimes(1)
    })
    expect(onSubmit.mock.calls[0]?.[0]).toMatchObject({ title: 'Edited', subtitle: 'World' })
  })

  it('preserves an in-progress edit when server data refetches (keepDirtyValues)', async () => {
    const user = userEvent.setup()
    const { rerender } = renderWithProviders(
      <EntityTextFieldsForm
        fieldDefs={FIELD_DEFS}
        values={{ title: 'Hello', subtitle: 'World' }}
        isSubmitting={false}
        serverErrorTitle={null}
        onSubmit={vi.fn()}
      />,
    )

    // User starts editing `title` (now dirty), leaves `subtitle` untouched.
    const title = screen.getByLabelText('title (text)')
    await user.clear(title)
    await user.type(title, 'Draft in progress')

    // A background refetch delivers NEW server values for both fields.
    rerender(
      <EntityTextFieldsForm
        fieldDefs={FIELD_DEFS}
        values={{ title: 'Server Title', subtitle: 'Server Subtitle' }}
        isSubmitting={false}
        serverErrorTitle={null}
        onSubmit={vi.fn()}
      />,
    )

    // Dirty field keeps the user's draft; untouched field adopts the new server value.
    await waitFor(() => {
      expect(screen.getByLabelText('subtitle (text)')).toHaveValue('Server Subtitle')
    })
    expect(screen.getByLabelText('title (text)')).toHaveValue('Draft in progress')
  })
})
