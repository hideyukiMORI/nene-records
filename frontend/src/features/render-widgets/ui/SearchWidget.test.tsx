import { cleanup, fireEvent, render, screen } from '@testing-library/react'
import { MemoryRouter, Route, Routes, useLocation } from 'react-router-dom'
import { afterEach, describe, expect, it } from 'vitest'
import type { Widget } from '@/entities/widget'
import { I18nProvider } from '@/shared/i18n'
import { SearchWidget } from './SearchWidget'

afterEach(cleanup)

function LocationProbe() {
  const location = useLocation()
  return <div data-testid="location">{location.pathname + location.search}</div>
}

const widget: Widget = {
  id: 1,
  widgetType: 'search',
  region: 'sidebar',
  displayOrder: 0,
  title: null,
  settings: {},
  createdAt: '',
  updatedAt: '',
}

function renderWidget() {
  return render(
    <I18nProvider>
      <MemoryRouter initialEntries={['/']}>
        <SearchWidget widget={widget} />
        <Routes>
          <Route path="*" element={<LocationProbe />} />
        </Routes>
      </MemoryRouter>
    </I18nProvider>,
  )
}

describe('SearchWidget', () => {
  it('navigates to the results page with the encoded query on submit', () => {
    renderWidget()
    fireEvent.change(screen.getByLabelText('Search'), { target: { value: 'hello world' } })
    fireEvent.submit(screen.getByRole('search'))
    expect(screen.getByTestId('location')).toHaveTextContent('/search?q=hello%20world')
  })

  it('does not navigate when the query is blank', () => {
    renderWidget()
    fireEvent.change(screen.getByLabelText('Search'), { target: { value: '   ' } })
    fireEvent.submit(screen.getByRole('search'))
    expect(screen.getByTestId('location')).toHaveTextContent('/')
  })
})
