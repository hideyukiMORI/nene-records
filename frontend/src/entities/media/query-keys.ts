export const mediaKeys = {
  all: ['media'] as const,
  list: () => [...mediaKeys.all, 'list'] as const,
  usages: (id: number) => [...mediaKeys.all, 'usages', id] as const,
}
