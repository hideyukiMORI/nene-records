import { cleanup, fireEvent, render, screen } from '@testing-library/react'
import { Link, RouterProvider, createMemoryRouter } from 'react-router-dom'
import { afterEach, describe, expect, it } from 'vitest'
import { useUnsavedChangesGuard } from './use-unsaved-changes-guard'

afterEach(cleanup)

function Guarded({ when }: { when: boolean }) {
  const blocker = useUnsavedChangesGuard(when)
  return (
    <div>
      <Link to="/b">go</Link>
      {blocker.state === 'blocked' ? (
        <button
          type="button"
          onClick={() => {
            blocker.proceed()
          }}
        >
          proceed
        </button>
      ) : null}
    </div>
  )
}

function renderAt(when: boolean) {
  const router = createMemoryRouter(
    [
      { path: '/', element: <Guarded when={when} /> },
      { path: '/b', element: <div>PageB</div> },
    ],
    { initialEntries: ['/'] },
  )
  render(<RouterProvider router={router} />)
}

describe('useUnsavedChangesGuard', () => {
  it('blocks in-app navigation when dirty, then proceeds on confirm', () => {
    renderAt(true)
    fireEvent.click(screen.getByText('go'))
    expect(screen.queryByText('PageB')).toBeNull()
    fireEvent.click(screen.getByText('proceed'))
    expect(screen.getByText('PageB')).toBeTruthy()
  })

  it('does not block when clean', () => {
    renderAt(false)
    fireEvent.click(screen.getByText('go'))
    expect(screen.getByText('PageB')).toBeTruthy()
  })
})
