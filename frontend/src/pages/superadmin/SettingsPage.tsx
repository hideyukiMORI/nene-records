import { useState } from 'react'
import { useSystemConfig, useUpdateSystemConfig } from '@/entities/system-config'
import type { TenantResolutionMode } from '@/entities/system-config'
import { Button, Input, Stack, Text } from '@/shared/ui'
import { useToast } from '@/shared/ui'

const MODES: { value: TenantResolutionMode; label: string; description: string }[] = [
  {
    value: 'single',
    label: 'シングルテナント（固定 org）',
    description: 'すべてのリクエストを特定の組織に固定する。単一組織の運用に最適。',
  },
  {
    value: 'subdomain',
    label: 'サブドメイン方式',
    description: 'acme.example.com のようにサブドメインで組織を判定する。本番 SaaS 向け。',
  },
  {
    value: 'path',
    label: 'パスプレフィックス方式',
    description: '/org/acme/... のように URL パスで組織を判定する。DNS 設定不要でシンプル。',
  },
]

export function SettingsPage() {
  const { showToast } = useToast()
  const { data, isLoading } = useSystemConfig()
  const update = useUpdateSystemConfig()

  // undefined = 未編集 → loaded data にフォールバック（useEffect 不要）
  const [mode, setMode] = useState<TenantResolutionMode | undefined>(undefined)
  const [orgSlug, setOrgSlug] = useState<string | undefined>(undefined)
  const [baseDomain, setBaseDomain] = useState<string | undefined>(undefined)

  const currentMode = mode ?? data?.tenantResolutionMode ?? 'single'
  const currentOrgSlug = orgSlug ?? data?.tenantOrgSlug ?? ''
  const currentBaseDomain = baseDomain ?? data?.tenantBaseDomain ?? 'localhost'

  function handleSubmit(e: React.SyntheticEvent) {
    e.preventDefault()
    update.mutate(
      {
        tenantResolutionMode: currentMode,
        tenantOrgSlug: currentOrgSlug.trim(),
        tenantBaseDomain: currentBaseDomain.trim(),
      },
      {
        onSuccess: () => {
          showToast('設定を保存しました。', 'success')
        },
        onError: () => {
          showToast('保存に失敗しました。', 'error')
        },
      },
    )
  }

  if (isLoading) {
    return <Text muted>Loading…</Text>
  }

  return (
    <Stack gap="lg">
      <div>
        <Text as="h1" variant="heading-md">
          システム設定
        </Text>
        <Text muted>テナント解決方式など、システム全体の設定を管理します。</Text>
      </div>

      <form onSubmit={handleSubmit}>
        <div className="rounded-lg border border-border bg-surface-raised p-6">
          <Text as="h2" variant="heading-sm">
            テナント解決方式
          </Text>
          <Text muted className="mt-1 text-sm">
            リクエストがどの組織に属するかを判定する方法を選択します。
          </Text>

          <Stack gap="sm" className="mt-5">
            {MODES.map((m) => (
              <div
                key={m.value}
                className={[
                  'flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition-colors',
                  currentMode === m.value
                    ? 'border-accent bg-accent/5'
                    : 'border-border bg-surface hover:bg-surface-raised/50',
                ].join(' ')}
              >
                <input
                  id={`mode-${m.value}`}
                  type="radio"
                  name="resolution_mode"
                  value={m.value}
                  checked={currentMode === m.value}
                  onChange={() => {
                    setMode(m.value)
                  }}
                  className="mt-0.5 accent-accent"
                />
                <div>
                  <label
                    htmlFor={`mode-${m.value}`}
                    className="cursor-pointer text-sm font-medium text-text-primary"
                  >
                    {m.label}
                  </label>
                  <p className="mt-0.5 text-xs text-text-muted">{m.description}</p>
                </div>
              </div>
            ))}
          </Stack>

          {/* モード別の補助入力 */}
          {currentMode === 'single' && (
            <div className="mt-5">
              <label
                htmlFor="tenant-org-slug"
                className="mb-1 block text-sm font-medium text-text-primary"
              >
                組織スラッグ
              </label>
              <Input
                id="tenant-org-slug"
                value={currentOrgSlug}
                onChange={(e) => {
                  setOrgSlug(e.target.value)
                }}
                placeholder="my-org"
                className="max-w-xs"
              />
              <p className="mt-1 text-xs text-text-muted">
                すべてのリクエストをこのスラッグの組織に紐付けます。
              </p>
            </div>
          )}

          {currentMode === 'subdomain' && (
            <div className="mt-5">
              <label
                htmlFor="tenant-base-domain"
                className="mb-1 block text-sm font-medium text-text-primary"
              >
                ベースドメイン
              </label>
              <Input
                id="tenant-base-domain"
                value={currentBaseDomain}
                onChange={(e) => {
                  setBaseDomain(e.target.value)
                }}
                placeholder="example.com"
                className="max-w-xs"
              />
              <p className="mt-1 text-xs text-text-muted">
                例: <code className="font-mono">example.com</code> → リクエストの{' '}
                <code className="font-mono">acme.example.com</code> から{' '}
                <code className="font-mono">acme</code> を抽出します。
              </p>
            </div>
          )}

          {currentMode === 'path' && (
            <div className="mt-5 rounded-md bg-surface p-3 text-xs text-text-muted">
              URL の先頭パスセグメントを組織スラッグとして使用します。
              <br />
              例: <code className="font-mono">/acme/api/v1/entities</code> →{' '}
              <code className="font-mono">acme</code>
            </div>
          )}

          <div className="mt-6">
            <Button type="submit" variant="primary" disabled={update.isPending}>
              {update.isPending ? '保存中…' : '設定を保存'}
            </Button>
          </div>
        </div>
      </form>

      {/* 現在の DB 保存済み設定 */}
      {data !== undefined && (
        <div className="rounded-lg border border-border bg-surface-raised p-6">
          <Text as="h2" variant="heading-sm">
            現在の設定（DB 保存値）
          </Text>
          <dl className="mt-3 space-y-2 text-sm">
            <div className="flex gap-3">
              <dt className="w-44 shrink-0 text-text-muted">解決方式</dt>
              <dd className="font-mono text-text-primary">{data.tenantResolutionMode}</dd>
            </div>
            {data.tenantOrgSlug !== '' && (
              <div className="flex gap-3">
                <dt className="w-44 shrink-0 text-text-muted">組織スラッグ</dt>
                <dd className="font-mono text-text-primary">{data.tenantOrgSlug}</dd>
              </div>
            )}
            {data.tenantResolutionMode === 'subdomain' && (
              <div className="flex gap-3">
                <dt className="w-44 shrink-0 text-text-muted">ベースドメイン</dt>
                <dd className="font-mono text-text-primary">{data.tenantBaseDomain}</dd>
              </div>
            )}
          </dl>
        </div>
      )}
    </Stack>
  )
}
