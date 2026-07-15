import { afterEach, describe, expect, it } from 'vitest'
import { cleanup, render, screen } from '@testing-library/react'
import { RouteProgress } from './RouteProgress'

describe('RouteProgress', () => {
  afterEach(cleanup)

  /**
   * The whole point of this component is what it does NOT draw. It renders while
   * the page's layout is still unknown, so any chrome it paints is a guess that a
   * `bare` page falsifies — the flash-of-foreign-header bug fixed four times over
   * (#879/#881/#883/#887). These cases pin that.
   */

  it('draws no chrome: no header, footer, nav or link', () => {
    const { container } = render(<RouteProgress theme="consumer" />)

    expect(container.querySelector('header')).toBeNull()
    expect(container.querySelector('footer')).toBeNull()
    expect(container.querySelector('nav')).toBeNull()
    expect(container.querySelector('a')).toBeNull()
    expect(container.querySelector('aside')).toBeNull()
  })

  it('is not scoped to .nene-public, which bare pages never render', () => {
    // `.nene-public` + `data-theme` come from PublicSiteShell, and a `bare` record
    // returns its view without that shell. A `.nene-public` ancestor here would
    // make the whole signal a no-op on exactly the sites that need it most.
    const { container } = render(<RouteProgress theme="consumer" />)

    expect(container.querySelector('.nene-public')).toBeNull()
    expect(container.firstElementChild).toHaveClass('nene-route-host')
  })

  it('carries the active theme so tokens resolve outside .nene-public', () => {
    const { container } = render(<RouteProgress theme="noir" />)

    expect(container.querySelector('.nene-route-host')).toHaveAttribute('data-theme', 'noir')
  })

  it('renders without a theme (a bare page has no site context to read)', () => {
    const { container } = render(<RouteProgress />)

    expect(container.querySelector('.nene-route-host')).not.toHaveAttribute('data-theme')
    expect(container.querySelector('.nene-route-progress__bar')).not.toBeNull()
  })

  it('shows the bar and the language-independent dots', () => {
    render(<RouteProgress theme="consumer" />)

    expect(screen.getByRole('status', { name: 'Loading' })).toBeInTheDocument()
    expect(document.querySelectorAll('.nene-dots i')).toHaveLength(3)
  })

  it('marks completion so the bar fills and fades instead of creeping', () => {
    const { container } = render(<RouteProgress theme="consumer" complete />)

    expect(container.querySelector('.nene-route-progress')).toHaveClass('is-complete')
  })

  it('does not mark completion while still loading', () => {
    const { container } = render(<RouteProgress theme="consumer" />)

    expect(container.querySelector('.nene-route-progress')).not.toHaveClass('is-complete')
  })
})
