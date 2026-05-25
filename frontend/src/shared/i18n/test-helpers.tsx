import { render, type RenderOptions, type RenderResult } from '@testing-library/react'
import type { ReactElement } from 'react'
import { I18nProvider } from './i18n-context'
import type { SupportedLocale } from './locales'

export interface RenderWithI18nOptions extends Omit<RenderOptions, 'wrapper'> {
  locale?: SupportedLocale
}

/**
 * Render a component wrapped in I18nProvider with the given locale.
 * Used in Vitest component tests.
 *
 * @example
 * const { getByText } = renderWithI18n(<MyComponent />, { locale: 'ja' })
 */
export function renderWithI18n(
  ui: ReactElement,
  { locale = 'en', ...options }: RenderWithI18nOptions = {},
): RenderResult {
  // Seed localStorage so I18nProvider detects the requested locale
  try {
    localStorage.setItem('nene-locale', locale)
  } catch {
    // ignore
  }
  return render(ui, {
    wrapper: ({ children }) => <I18nProvider>{children}</I18nProvider>,
    ...options,
  })
}

/**
 * Storybook decorator that wraps stories with I18nProvider.
 * Import in .storybook/preview.tsx to apply globally.
 *
 * @example
 * // .storybook/preview.tsx
 * import { withI18n } from '@/shared/i18n/test-helpers'
 * export const decorators = [withI18n()]
 */
export function withI18n(locale: SupportedLocale = 'en') {
  return function I18nDecorator(Story: () => ReactElement): ReactElement {
    try {
      localStorage.setItem('nene-locale', locale)
    } catch {
      // ignore
    }
    return (
      <I18nProvider>
        <Story />
      </I18nProvider>
    )
  }
}
