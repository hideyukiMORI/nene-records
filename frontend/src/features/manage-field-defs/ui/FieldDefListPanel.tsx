import type { FieldDef } from '@/entities/field-def'
import { useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import type { FieldDataType } from '@/entities/field-def'
import type { ContentRegion } from '@/shared/lib/resolve-layout'
import { Button, Card, EmptyState, ErrorState, LoadingState, Stack, Text } from '@/shared/ui'

const REGION_LABEL_KEYS: Record<ContentRegion, MessageKey> = {
  main: 'admin.region.main',
  sidebar: 'admin.region.sidebar',
  aside: 'admin.region.aside',
}

const DATA_TYPE_LABEL_KEYS: Record<FieldDataType, MessageKey> = {
  text: 'admin.fieldDefs.dataType.text',
  markdown: 'admin.fieldDefs.dataType.markdown',
  html: 'admin.fieldDefs.dataType.html',
  bundle: 'admin.fieldDefs.dataType.bundle',
  int: 'admin.fieldDefs.dataType.int',
  enum: 'admin.fieldDefs.dataType.enum',
  bool: 'admin.fieldDefs.dataType.bool',
  datetime: 'admin.fieldDefs.dataType.datetime',
  relation: 'admin.fieldDefs.dataType.relation',
}

export interface FieldDefListPanelProps {
  items: FieldDef[]
  canManageSchema: boolean
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  isDeleting: boolean
  onRetry: () => void
  onEdit: (fieldDef: FieldDef) => void
  onDelete: (fieldDef: FieldDef) => void
}

export function FieldDefListPanel({
  items,
  canManageSchema,
  isLoading,
  isError,
  errorTitle,
  isDeleting,
  onRetry,
  onEdit,
  onDelete,
}: FieldDefListPanelProps) {
  const { t } = useTranslation()

  if (isLoading) {
    return <LoadingState>{t('admin.fieldDefs.list.loading')}</LoadingState>
  }

  if (isError) {
    return (
      <ErrorState
        title={t('admin.fieldDefs.list.error')}
        message={errorTitle ?? t('common.error.unknown')}
        onRetry={onRetry}
        retryLabel={t('common.actions.retry')}
      />
    )
  }

  if (items.length === 0) {
    return (
      <EmptyState
        title={t('admin.fieldDefs.list.empty.title')}
        description={t('admin.fieldDefs.list.empty.description')}
      />
    )
  }

  return (
    <ul className="flex flex-col gap-stack-sm">
      {items.map((item) => (
        <Card
          as="li"
          key={String(item.id)}
          padding="row"
          className="flex items-center justify-between gap-inline-md"
        >
          <Stack gap="xs">
            <Text as="span" variant="heading-sm">
              {item.fieldKey}
            </Text>
            <Text as="span" muted>
              {t(DATA_TYPE_LABEL_KEYS[item.dataType])}
              {item.region !== null ? ` · ${t(REGION_LABEL_KEYS[item.region])}` : ''}
            </Text>
          </Stack>
          <div className="flex items-center gap-inline-sm">
            {canManageSchema ? (
              <>
                <Button
                  variant="secondary"
                  size="sm"
                  onClick={() => {
                    onEdit(item)
                  }}
                >
                  {t('common.actions.edit')}
                </Button>
                <Button
                  variant="danger"
                  size="sm"
                  disabled={isDeleting}
                  onClick={() => {
                    onDelete(item)
                  }}
                >
                  {t('common.actions.delete')}
                </Button>
              </>
            ) : null}
          </div>
        </Card>
      ))}
    </ul>
  )
}
