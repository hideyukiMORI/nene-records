export const orgExportKeys = {
  all: ['org-export'] as const,
  export: (orgId: number) => [...orgExportKeys.all, 'export', orgId] as const,
}
