import { afterEach, describe, expect, it } from 'vitest'
import { cleanup, screen } from '@testing-library/react'
import { renderWithI18n } from '@/shared/i18n/test-helpers'
import { PublicRecordDetailView } from './PublicRecordDetailView'

afterEach(cleanup)

describe('PublicRecordDetailView', () => {
  it('renders a skeleton — never a loading text — while the record body loads (#905)', () => {
    const { container } = renderWithI18n(
      <PublicRecordDetailView
        entity={null}
        fieldRows={[]}
        entityTypeSlugById={{}}
        entityTypePatternById={{}}
        isLoading={true}
        isError={false}
        errorTitle={null}
        onRetry={() => {}}
      />,
    )

    // #894's rule: the loading signal is shape, not words. The old text leaked
    // through in every locale, so pin the absence of all of them.
    expect(screen.queryByText(/読み込み中/)).toBeNull()
    expect(screen.queryByText(/Loading/)).toBeNull()
    expect(container.querySelector('[aria-busy="true"]')).not.toBeNull()
    expect(container.querySelector('.sk-article')).not.toBeNull()
  })
})
