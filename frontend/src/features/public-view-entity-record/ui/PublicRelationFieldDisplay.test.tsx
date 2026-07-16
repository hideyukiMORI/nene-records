import { afterEach, describe, expect, it, vi } from 'vitest'
import { cleanup, screen } from '@testing-library/react'
import type { RelationFieldDef } from '@/entities/field-def'
import { renderWithI18n } from '@/shared/i18n/test-helpers'
import { PublicRelationFieldDisplay } from './PublicRelationFieldDisplay'

vi.mock('../hooks/use-public-relation-field-display', () => ({
  usePublicRelationFieldDisplay: () => ({
    targets: [],
    isLoading: true,
    isError: false,
    errorTitle: null,
    refetch: () => {},
  }),
}))

afterEach(cleanup)

const fieldDef = {
  id: 1,
  fieldKey: 'related_works',
  dataType: 'relation',
} as unknown as RelationFieldDef

describe('PublicRelationFieldDisplay', () => {
  it('keeps the field key and shows a line skeleton — no loading text — while resolving (#905)', () => {
    const { container } = renderWithI18n(
      <PublicRelationFieldDisplay
        entityId={1}
        fieldDef={fieldDef}
        entityTypeSlugById={{}}
        entityTypePatternById={{}}
      />,
    )

    expect(screen.getByText('related_works')).not.toBeNull()
    expect(screen.queryByText(/読み込み中/)).toBeNull()
    expect(screen.queryByText(/Loading/)).toBeNull()
    expect(container.querySelector('dd[aria-busy="true"] .sk-line')).not.toBeNull()
  })
})
