export const popularEntityKeys = {
  all: ['popular-entities'] as const,
  list: (days: number, limit: number) => [...popularEntityKeys.all, 'list', days, limit] as const,
}
