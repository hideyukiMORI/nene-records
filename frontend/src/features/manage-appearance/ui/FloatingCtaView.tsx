import type { ReactNode } from 'react'
import { useTranslation } from '@/shared/i18n'
import {
  FLOATING_CTA_ICONS,
  joinList,
  MAX_FLOATING_CTA_BOTTOM_OFFSET,
  MAX_FLOATING_CTA_DELAY_SECONDS,
  MAX_FLOATING_CTA_SCROLL_PX,
  parseList,
  safeHref,
} from '@/shared/lib/floating-cta'
import { Button, Card, Stack, Text } from '@/shared/ui'
import type { FloatingCtaPageState } from '../hooks/useFloatingCtaPage'

function Row({ label, children }: { label: string; children: ReactNode }) {
  return (
    <label className="flex items-center justify-between gap-inline-md">
      <span className="font-chrome text-caption font-semibold text-text-primary">{label}</span>
      {children}
    </label>
  )
}

function TextInput({
  value,
  onChange,
  placeholder,
}: {
  value: string
  onChange: (value: string) => void
  placeholder?: string
}) {
  return (
    <input
      type="text"
      className="w-56 rounded-sm border border-border bg-surface px-inline-sm py-stack-xs font-sans text-body text-text-primary"
      value={value}
      placeholder={placeholder}
      onChange={(event) => {
        onChange(event.target.value)
      }}
    />
  )
}

function Checkbox({
  checked,
  onChange,
  label,
}: {
  checked: boolean
  onChange: (checked: boolean) => void
  label: string
}) {
  return (
    <label className="flex items-center gap-inline-sm">
      <input
        type="checkbox"
        className="h-4 w-4 accent-accent"
        checked={checked}
        onChange={(event) => {
          onChange(event.target.checked)
        }}
      />
      <span className="font-chrome text-caption font-semibold text-text-primary">{label}</span>
    </label>
  )
}

/**
 * Admin editor for the public-site floating CTA (#982 P1): enable, position
 * (bottom-right / bottom-left), structured content (emoji + label + sub), link and
 * display conditions (types / URL globs / exclude). Saves to the `floating_cta`
 * setting; the public SSR shell renders it as chrome on matching pages.
 */
export function FloatingCtaView({
  draft,
  setConfig,
  setContent,
  setLink,
  setConditions,
  save,
  isSaving,
  isDirty,
  isLoading,
}: FloatingCtaPageState) {
  const { t } = useTranslation()
  const disabled = isLoading || isSaving
  const urlUnsafe = draft.link.url.trim() !== '' && safeHref(draft.link.url) === ''

  return (
    <Card padding="md">
      <Stack gap="md">
        <Text muted variant="caption">
          {t('admin.floatingCta.intro')}
        </Text>

        <Checkbox
          checked={draft.enabled}
          label={t('admin.floatingCta.enabled')}
          onChange={(enabled) => {
            setConfig({ enabled })
          }}
        />

        <Stack gap="sm">
          <Row label={t('admin.floatingCta.position')}>
            <select
              className="w-56 rounded-sm border border-border bg-surface px-inline-sm py-stack-xs font-sans text-body text-text-primary"
              value={draft.position}
              onChange={(event) => {
                setConfig({ position: event.target.value === 'bl' ? 'bl' : 'br' })
              }}
            >
              <option value="br">{t('admin.floatingCta.positionBr')}</option>
              <option value="bl">{t('admin.floatingCta.positionBl')}</option>
            </select>
          </Row>
          <Row label={t('admin.floatingCta.accent')}>
            <TextInput
              value={draft.accent}
              placeholder="#1f6feb"
              onChange={(accent) => {
                setConfig({ accent })
              }}
            />
          </Row>
          <Row label={t('admin.floatingCta.bottomOffset')}>
            <div className="flex items-center gap-inline-sm">
              <input
                type="number"
                min={0}
                max={MAX_FLOATING_CTA_BOTTOM_OFFSET}
                step={4}
                className="w-24 rounded-sm border border-border bg-surface px-inline-sm py-stack-xs font-sans text-body text-text-primary"
                value={draft.bottomOffset}
                onChange={(event) => {
                  const parsed = Number.parseInt(event.target.value, 10)
                  const clamped = Number.isFinite(parsed)
                    ? Math.max(0, Math.min(parsed, MAX_FLOATING_CTA_BOTTOM_OFFSET))
                    : 0
                  setConfig({ bottomOffset: clamped })
                }}
              />
              <Text muted variant="caption">
                {t('admin.floatingCta.bottomOffsetHelp')}
              </Text>
            </div>
          </Row>
          <Checkbox
            checked={draft.dismissible}
            label={t('admin.floatingCta.dismissible')}
            onChange={(dismissible) => {
              setConfig({ dismissible })
            }}
          />
          <Text muted variant="caption">
            {t('admin.floatingCta.dismissibleHelp')}
          </Text>
          <Row label={t('admin.floatingCta.trigger')}>
            <select
              className="w-56 rounded-sm border border-border bg-surface px-inline-sm py-stack-xs font-sans text-body text-text-primary"
              value={draft.trigger}
              onChange={(event) => {
                const value = event.target.value
                const trigger = value === 'delay' || value === 'scroll' ? value : 'always'
                // Units differ (seconds vs px), so reset to a sensible per-trigger default.
                const triggerValue = trigger === 'delay' ? 3 : trigger === 'scroll' ? 400 : 0
                setConfig({ trigger, triggerValue })
              }}
            >
              <option value="always">{t('admin.floatingCta.triggerAlways')}</option>
              <option value="delay">{t('admin.floatingCta.triggerDelay')}</option>
              <option value="scroll">{t('admin.floatingCta.triggerScroll')}</option>
            </select>
          </Row>
          {draft.trigger !== 'always' ? (
            <Row
              label={
                draft.trigger === 'delay'
                  ? t('admin.floatingCta.triggerDelaySeconds')
                  : t('admin.floatingCta.triggerScrollPx')
              }
            >
              <input
                type="number"
                min={1}
                max={
                  draft.trigger === 'delay'
                    ? MAX_FLOATING_CTA_DELAY_SECONDS
                    : MAX_FLOATING_CTA_SCROLL_PX
                }
                step={draft.trigger === 'delay' ? 1 : 50}
                className="w-24 rounded-sm border border-border bg-surface px-inline-sm py-stack-xs font-sans text-body text-text-primary"
                value={draft.triggerValue}
                onChange={(event) => {
                  const max =
                    draft.trigger === 'delay'
                      ? MAX_FLOATING_CTA_DELAY_SECONDS
                      : MAX_FLOATING_CTA_SCROLL_PX
                  const parsed = Number.parseInt(event.target.value, 10)
                  const clamped = Number.isFinite(parsed) ? Math.max(1, Math.min(parsed, max)) : 1
                  setConfig({ triggerValue: clamped })
                }}
              />
            </Row>
          ) : null}
        </Stack>

        <Stack gap="sm">
          <Row label={t('admin.floatingCta.iconId')}>
            <div className="flex flex-wrap items-center gap-inline-sm">
              <button
                type="button"
                aria-pressed={draft.content.iconId === ''}
                className={
                  draft.content.iconId === ''
                    ? 'rounded-sm border-1.5 border-accent bg-surface px-inline-sm py-stack-xs font-chrome text-caption text-text-primary'
                    : 'rounded-sm border border-border bg-surface px-inline-sm py-stack-xs font-chrome text-caption text-text-secondary'
                }
                onClick={() => {
                  setContent({ iconId: '' })
                }}
              >
                {t('admin.floatingCta.iconNone')}
              </button>
              {FLOATING_CTA_ICONS.map((icon) => (
                <button
                  key={icon.id}
                  type="button"
                  title={icon.id}
                  aria-label={icon.id}
                  aria-pressed={draft.content.iconId === icon.id}
                  className={
                    draft.content.iconId === icon.id
                      ? 'inline-flex h-9 w-9 items-center justify-center rounded-sm border-1.5 border-accent bg-surface text-text-primary'
                      : 'inline-flex h-9 w-9 items-center justify-center rounded-sm border border-border bg-surface text-text-secondary'
                  }
                  onClick={() => {
                    setContent({ iconId: icon.id })
                  }}
                  dangerouslySetInnerHTML={{ __html: icon.svg }}
                />
              ))}
            </div>
          </Row>
          <Row label={t('admin.floatingCta.icon')}>
            <TextInput
              value={draft.content.icon}
              placeholder="📅"
              onChange={(icon) => {
                setContent({ icon })
              }}
            />
          </Row>
          <Row label={t('admin.floatingCta.label')}>
            <TextInput
              value={draft.content.label}
              onChange={(label) => {
                setContent({ label })
              }}
            />
          </Row>
          <Row label={t('admin.floatingCta.sub')}>
            <TextInput
              value={draft.content.sub}
              onChange={(sub) => {
                setContent({ sub })
              }}
            />
          </Row>
        </Stack>

        <Stack gap="sm">
          <Row label={t('admin.floatingCta.url')}>
            <TextInput
              value={draft.link.url}
              placeholder="https://…"
              onChange={(url) => {
                setLink({ url })
              }}
            />
          </Row>
          {urlUnsafe ? (
            <Text muted variant="caption">
              {t('admin.floatingCta.urlUnsafe')}
            </Text>
          ) : null}
          <Checkbox
            checked={draft.link.newTab}
            label={t('admin.floatingCta.newTab')}
            onChange={(newTab) => {
              setLink({ newTab })
            }}
          />
        </Stack>

        <Stack gap="sm">
          <Text muted variant="caption">
            {t('admin.floatingCta.conditionsIntro')}
          </Text>
          <Row label={t('admin.floatingCta.condTypes')}>
            <TextInput
              value={joinList(draft.conditions.types)}
              placeholder="page, post"
              onChange={(text) => {
                setConditions({ types: parseList(text) })
              }}
            />
          </Row>
          <Row label={t('admin.floatingCta.condUrls')}>
            <TextInput
              value={joinList(draft.conditions.urlGlobs)}
              placeholder="/services*"
              onChange={(text) => {
                setConditions({ urlGlobs: parseList(text) })
              }}
            />
          </Row>
          <Row label={t('admin.floatingCta.condExclude')}>
            <TextInput
              value={joinList(draft.conditions.exclude)}
              placeholder="/admin*"
              onChange={(text) => {
                setConditions({ exclude: parseList(text) })
              }}
            />
          </Row>
        </Stack>

        <div>
          <Button onClick={save} disabled={disabled || !isDirty}>
            {isSaving ? t('admin.floatingCta.saving') : t('admin.floatingCta.save')}
          </Button>
        </div>
      </Stack>
    </Card>
  )
}
