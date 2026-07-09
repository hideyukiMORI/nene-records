import { cleanup, renderHook } from '@testing-library/react'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { useManageSiteSettingsPage } from './useManageSiteSettingsPage'

// Mock the entity queries so the hook returns a fixed settings list; we only
// care about which keys survive the DEDICATED_UI_KEYS filter (#541).
vi.mock('@/entities/setting', () => {
  const make = (settingKey: string) => ({
    settingKey,
    label: settingKey,
    dataType: 'text' as const,
    defaultValue: null,
    isPublic: true,
    value: '',
    updatedAt: null,
  })
  const items = [
    'site_name',
    'tagline',
    'theme_overrides',
    'header_config',
    'footer_config',
    'home_hero',
    'layout_config',
    'active_theme',
    'front_page',
  ].map(make)

  return {
    useSettingList: () => ({
      data: { items },
      isLoading: false,
      isError: false,
      refetch: () => {},
    }),
    useSettingRevisions: () => ({ data: { items: [] }, isLoading: false, isError: false }),
    useUpdateSetting: () => ({ mutateAsync: () => Promise.resolve(undefined), isPending: false }),
  }
})

afterEach(cleanup)

describe('useManageSiteSettingsPage', () => {
  const visibleKeys = (): string[] =>
    renderHook(() => useManageSiteSettingsPage()).result.current.items.map(
      (item) => item.settingKey,
    )

  it('hides settings that have a dedicated structured editor, so no raw JSON shows in the generic list (#541)', () => {
    const keys = visibleKeys()

    for (const dedicated of [
      'theme_overrides',
      'header_config',
      'footer_config',
      'home_hero',
      'layout_config',
      'active_theme',
      'front_page',
    ]) {
      expect(keys).not.toContain(dedicated)
    }
  })

  it('keeps plain text settings in the generic list', () => {
    const keys = visibleKeys()

    expect(keys).toContain('site_name')
    expect(keys).toContain('tagline')
  })
})
