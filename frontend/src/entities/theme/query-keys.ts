/**
 * Query-key factory for the theme entity. Matches the `xKeys` convention used by
 * every other entity module (settingKeys / widgetKeys / …): a shared `all`
 * prefix so a mutation can invalidate both the public and admin theme lists in
 * one call, and named sub-keys so the lists never drift apart by hand.
 */
export const themeKeys = {
  all: ['themes'] as const,
  publicList: () => [...themeKeys.all, 'public'] as const,
  adminList: () => [...themeKeys.all, 'admin'] as const,
  authoringGuide: () => [...themeKeys.all, 'authoring-guide'] as const,
}
