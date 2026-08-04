import { afterEach, describe, expect, it } from 'vitest'
import { cleanup, render } from '@testing-library/react'
import { usePublicDocumentTitle } from './use-public-document-title'

afterEach(cleanup)

function Probe({ pageTitle }: { pageTitle: string | null | undefined }) {
  usePublicDocumentTitle(pageTitle, '彩音インターナショナル株式会社')
  return null
}

describe('usePublicDocumentTitle', () => {
  it('sets the composed title and skips the suffix when the site name is already carried (#909)', () => {
    render(<Probe pageTitle="サービスと料金｜彩音インターナショナル株式会社" />)
    expect(document.title).toBe('サービスと料金｜彩音インターナショナル株式会社')
  })

  it('appends the site name when the page title does not carry it', () => {
    render(<Probe pageTitle="お知らせ" />)
    expect(document.title).toBe('お知らせ — 彩音インターナショナル株式会社')
  })

  it('restores the site name on unmount so the next page never inherits a stale title', () => {
    const { unmount } = render(<Probe pageTitle="お知らせ" />)
    unmount()
    expect(document.title).toBe('彩音インターナショナル株式会社')
  })

  it('leaves the title alone when the page delegates (undefined)', () => {
    document.title = '委譲先のタイトル'
    const { unmount } = render(<Probe pageTitle={undefined} />)
    expect(document.title).toBe('委譲先のタイトル')
    unmount()
    expect(document.title).toBe('委譲先のタイトル')
  })
})
