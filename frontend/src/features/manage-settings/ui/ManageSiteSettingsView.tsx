import { useCallback, useMemo, useState } from 'react'
import {
  useSettingList,
  useSettingRevisions,
  useUpdateSetting,
  type SettingItem,
} from '@/entities/setting'
import { Button, Input, Stack, Text } from '@/shared/ui'

function SettingField({
  item,
  isSaving,
  onSave,
}: {
  item: SettingItem
  isSaving: boolean
  onSave: (settingKey: string, value: string) => Promise<void>
}) {
  const [value, setValue] = useState(item.value)

  const inputId = `setting-${item.settingKey}`

  return (
    <Stack gap="sm">
      {item.dataType === 'markdown' ? (
        <div className="flex flex-col gap-stack-xs">
          <label htmlFor={inputId} className="font-sans text-body font-medium text-text-primary">
            {item.label}
          </label>
          <textarea
            id={inputId}
            value={value}
            disabled={isSaving}
            rows={4}
            onChange={(event) => {
              setValue(event.target.value)
            }}
            className="rounded-md border border-border bg-surface-raised px-inline-md py-stack-sm font-sans text-body text-text-primary shadow-sm focus-visible:outline-none focus-visible:shadow-focus disabled:cursor-not-allowed disabled:opacity-50"
          />
          <Text muted variant="caption">
            Markdown · {item.isPublic ? 'Public' : 'Admin only'}
          </Text>
        </div>
      ) : (
        <Input
          id={inputId}
          label={item.label}
          value={value}
          disabled={isSaving}
          onChange={(event) => {
            setValue(event.target.value)
          }}
        />
      )}
      <Button
        variant="secondary"
        size="sm"
        disabled={isSaving || value === item.value}
        onClick={() => {
          void onSave(item.settingKey, value)
        }}
      >
        {isSaving ? 'Saving…' : 'Save'}
      </Button>
    </Stack>
  )
}

function SettingRevisionsPanel({ settingKey }: { settingKey: string }) {
  const revisionsQuery = useSettingRevisions(settingKey)

  if (revisionsQuery.isLoading) {
    return <Text muted>Loading history…</Text>
  }

  if (revisionsQuery.isError) {
    return <Text muted>Could not load revision history.</Text>
  }

  const items = revisionsQuery.data?.items ?? []

  if (items.length === 0) {
    return <Text muted>No revisions yet.</Text>
  }

  return (
    <Stack gap="xs">
      {items.map((revision) => (
        <Text key={revision.id} muted variant="caption">
          {revision.createdAt} · {revision.action}
          {revision.previousValue !== null ? ` · from "${revision.previousValue}"` : ''}
        </Text>
      ))}
    </Stack>
  )
}

export function ManageSiteSettingsView() {
  const listQuery = useSettingList()
  const updateMutation = useUpdateSetting()
  const [expandedKey, setExpandedKey] = useState<string | null>(null)

  const saveSetting = useCallback(
    async (settingKey: string, value: string) => {
      await updateMutation.mutateAsync({ settingKey, input: { value } })
    },
    [updateMutation],
  )

  const items = useMemo(() => listQuery.data?.items ?? [], [listQuery.data?.items])

  if (listQuery.isLoading) {
    return <Text muted>Loading settings…</Text>
  }

  if (listQuery.isError) {
    return (
      <Stack gap="sm">
        <Text muted>Could not load site settings.</Text>
        <Button variant="secondary" size="sm" onClick={() => void listQuery.refetch()}>
          Retry
        </Button>
      </Stack>
    )
  }

  return (
    <Stack gap="lg">
      {items.map((item) => (
        <section
          key={item.settingKey}
          className="rounded-md border border-border bg-surface-raised p-inline-md shadow-sm"
        >
          <Stack gap="md">
            <SettingField
              key={`${item.settingKey}:${item.updatedAt ?? 'default'}`}
              item={item}
              isSaving={updateMutation.isPending}
              onSave={saveSetting}
            />
            <Button
              variant="secondary"
              size="sm"
              onClick={() => {
                setExpandedKey((current) => (current === item.settingKey ? null : item.settingKey))
              }}
            >
              {expandedKey === item.settingKey ? 'Hide history' : 'Show history'}
            </Button>
            {expandedKey === item.settingKey ? (
              <SettingRevisionsPanel settingKey={item.settingKey} />
            ) : null}
          </Stack>
        </section>
      ))}
    </Stack>
  )
}
