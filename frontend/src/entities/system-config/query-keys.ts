export const systemConfigKeys = {
  all: ['system-config'] as const,
  detail: () => [...systemConfigKeys.all, 'detail'] as const,
}
