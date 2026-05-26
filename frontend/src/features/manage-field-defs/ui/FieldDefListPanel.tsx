import type { FieldDef } from '@/entities/field-def'
import { useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import type { FieldDataType } from '@/entities/field-def'
import { Button, EmptyState, Stack, Text } from '@/shared/ui'

const DATA_TYPE_LABEL_KEYS: Record<FieldDataType, MessageKey> = {
  text: 'admin.fieldDefs.dataType.text',
  markdown: 'admin.fieldDefs.dataType.markdown',
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
    return <Text muted>{t('admin.fieldDefs.list.loading')}</Text>
  }

  if (isError) {
    return (
      <Stack gap="sm">
        <Text variant="heading-sm">{t('admin.fieldDefs.list.error')}</Text>
        <Text muted>{errorTitle ?? t('common.error.unknown')}</Text>
        <Button variant="secondary" onClick={onRetry}>
          {t('common.actions.retry')}
        </Button>
      </Stack>
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
        <li
          key={String(item.id)}
          className="flex items-center justify-between gap-inline-md rounded-md border border-border bg-surface-raised px-inline-md py-stack-sm shadow-sm"
        >
          <Stack gap="xs">
            <Text as="span" variant="heading-sm">
              {item.fieldKey}
            </Text>
            <Text as="span" muted>
              {t(DATA_TYPE_LABEL_KEYS[item.dataType])}
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
        </li>
      ))}
    </ul>
  )
}
