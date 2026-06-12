export const dashboardKeys = {
  all: ['dashboard'] as const,
  summary: () => [...dashboardKeys.all, 'summary'] as const,
  accessStats: (from: string, to: string) =>
    [...dashboardKeys.all, 'access-stats', from, to] as const,
}
