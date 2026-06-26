export const accountKeys = {
  all: ['account'] as const,
  detail: () => [...accountKeys.all, 'detail'] as const,
}
