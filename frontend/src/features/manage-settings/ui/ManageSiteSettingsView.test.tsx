import { cleanup, fireEvent, render, screen } from '@testing-library/react'
import { afterEach, describe, expect, it, vi } from 'vitest'
import type { SettingItem } from '@/entities/setting'
import { I18nProvider } from '@/shared/i18n'
import { ManageSiteSettingsView } from './ManageSiteSettingsView'

afterEach(cleanup)

const consentItem: SettingItem = {
  settingKey: 'analytics_consent_default',
  label: 'Analytics consent default',
  dataType: 'text',
  defaultValue: 'denied',
  isPublic: true,
  value: 'denied',
  updatedAt: null,
}

function renderView(item: SettingItem = consentItem) {
  const onSave = vi.fn().mockResolvedValue(undefined)
  render(
    <I18nProvider>
      <ManageSiteSettingsView
        items={[item]}
        isLoading={false}
        isError={false}
        isSaving={false}
        expandedKey={null}
        revisions={[]}
        revisionsLoading={false}
        revisionsError={false}
        canManageSettings={true}
        onRetry={vi.fn()}
        onSave={onSave}
        onToggleExpanded={vi.fn()}
      />
    </I18nProvider>,
  )
  return { onSave }
}

describe('ManageSiteSettingsView · analytics_consent_default', () => {
  it('renders a denied/granted dropdown reflecting the current value', () => {
    renderView()

    const select = screen.getByLabelText('Analytics consent default')
    expect(select.tagName).toBe('SELECT')
    expect((select as HTMLSelectElement).value).toBe('denied')
    expect(screen.getByRole('option', { name: 'Denied (EU-safe default)' })).toBeTruthy()
    expect(screen.getByRole('option', { name: 'Granted' })).toBeTruthy()
  })

  it('saves the selected value', () => {
    const { onSave } = renderView()

    fireEvent.change(screen.getByLabelText('Analytics consent default'), {
      target: { value: 'granted' },
    })
    fireEvent.click(screen.getByRole('button', { name: 'Save' }))

    expect(onSave).toHaveBeenCalledWith('analytics_consent_default', 'granted')
  })

  it('keeps a plain text input for other settings', () => {
    renderView({ ...consentItem, settingKey: 'site_name', label: 'Site name', value: 'NeNe' })

    const input = screen.getByLabelText('Site name')
    expect(input.tagName).toBe('INPUT')
  })
})
