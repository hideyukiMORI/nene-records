export const settingKeys = {
  all: ['settings'] as const,
  adminList: () => [...settingKeys.all, 'admin-list'] as const,
  publicList: () => [...settingKeys.all, 'public-list'] as const,
  revisions: (settingKey: string) => [...settingKeys.all, 'revisions', settingKey] as const,
}
