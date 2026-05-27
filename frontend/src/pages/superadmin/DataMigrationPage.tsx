import { DataMigrationView, useDataMigrationPage } from '@/features/manage-data-migration'

export function DataMigrationPage() {
  const page = useDataMigrationPage()
  return <DataMigrationView {...page} />
}
