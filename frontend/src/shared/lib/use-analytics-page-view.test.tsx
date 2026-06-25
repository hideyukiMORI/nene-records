import { cleanup, fireEvent, render, screen } from '@testing-library/react'
import { Link, Outlet, RouterProvider, createMemoryRouter } from 'react-router-dom'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { useAnalyticsPageView } from './use-analytics-page-view'
import type { WebAnalyticsClientConfig } from './web-analytics'

afterEach(() => {
  cleanup()
  delete window.gtag
})

const ga4: WebAnalyticsClientConfig = {
  gtmId: null,
  ga4Id: 'G-XYZ987',
  consentDefault: 'denied',
  enabled: true,
}

// The hook lives in the persistent layout route (like PublicShell), so it
// survives child navigations and observes the location change.
function Layout({ config }: { config: WebAnalyticsClientConfig }) {
  useAnalyticsPageView(config)
  return (
    <>
      <Link to="/posts/1">go</Link>
      <Outlet />
    </>
  )
}

function renderRouter(config: WebAnalyticsClientConfig) {
  const router = createMemoryRouter(
    [
      {
        path: '/',
        element: <Layout config={config} />,
        children: [
          { index: true, element: <div>Home</div> },
          { path: 'posts/1', element: <div>Post</div> },
        ],
      },
    ],
    { initialEntries: ['/'] },
  )
  render(<RouterProvider router={router} />)
}

describe('useAnalyticsPageView', () => {
  it('skips the initial render and reports on subsequent navigations', () => {
    const gtag = vi.fn()
    window.gtag = gtag

    renderRouter(ga4)
    // Initial load is counted server-side → no client page_view yet.
    expect(gtag).not.toHaveBeenCalled()

    fireEvent.click(screen.getByText('go'))

    expect(gtag).toHaveBeenCalledWith(
      'event',
      'page_view',
      expect.objectContaining({ page_path: '/posts/1' }),
    )
  })

  it('does nothing when analytics is disabled', () => {
    const gtag = vi.fn()
    window.gtag = gtag

    renderRouter({ ...ga4, enabled: false, ga4Id: null })
    fireEvent.click(screen.getByText('go'))

    expect(gtag).not.toHaveBeenCalled()
  })
})
