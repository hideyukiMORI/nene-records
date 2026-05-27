export const dataMigrationKeys = {
  all: ['data-migration'] as const,
  status: () => [...dataMigrationKeys.all, 'status'] as const,
}
