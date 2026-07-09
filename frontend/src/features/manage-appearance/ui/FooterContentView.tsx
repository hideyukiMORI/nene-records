import { useTranslation } from '@/shared/i18n'
import { SOCIAL_PLATFORMS, type SocialPlatform } from '@/shared/lib/footer-config'
import { Button, Card, Stack, Text } from '@/shared/ui'
import { SocialIcon } from '@/shared/ui/icons/SocialIcons'
import type { FooterConfigPageState } from '../hooks/useFooterConfigPage'

const inputClass =
  'w-56 rounded-sm border border-border bg-surface px-inline-sm py-stack-xs font-sans text-body text-text-primary'

/**
 * Admin editor for public-site footer content (#766): social icon links, the
 * legal link bar (privacy / terms / 特商法 …), and the Powered-by visibility.
 * Saves to the `footer_config` setting; the public shell renders the bottom bar.
 */
export function FooterContentView({
  draft,
  setSocial,
  setLegalLinks,
  setShowPoweredBy,
  save,
  isSaving,
  isDirty,
  isLoading,
}: FooterConfigPageState) {
  const { t } = useTranslation()
  const disabled = isLoading || isSaving

  return (
    <Card padding="md">
      <Stack gap="md">
        <Text muted variant="caption">
          {t('admin.footerContent.intro')}
        </Text>

        {/* SNS リンク */}
        <Stack gap="sm">
          <Text as="h3" variant="heading-sm">
            {t('admin.footerContent.social')}
          </Text>
          {draft.social.map((link, index) => (
            <div key={index} className="flex items-center gap-inline-sm">
              <SocialIcon platform={link.platform} size={16} />
              <select
                className="rounded-sm border border-border bg-surface px-inline-sm py-stack-xs font-sans text-body text-text-primary"
                value={link.platform}
                aria-label={t('admin.footerContent.platform')}
                onChange={(event) => {
                  const next = [...draft.social]
                  next[index] = { ...link, platform: event.target.value as SocialPlatform }
                  setSocial(next)
                }}
              >
                {SOCIAL_PLATFORMS.map((platform) => (
                  <option key={platform} value={platform}>
                    {platform}
                  </option>
                ))}
              </select>
              <input
                type="text"
                className={inputClass}
                value={link.url}
                placeholder="https://…"
                aria-label={t('admin.footerContent.url')}
                onChange={(event) => {
                  const next = [...draft.social]
                  next[index] = { ...link, url: event.target.value }
                  setSocial(next)
                }}
              />
              <Button
                variant="ghost"
                size="sm"
                onClick={() => {
                  setSocial(draft.social.filter((_, i) => i !== index))
                }}
              >
                {t('common.actions.delete')}
              </Button>
            </div>
          ))}
          <div>
            <Button
              variant="secondary"
              size="sm"
              disabled={disabled}
              onClick={() => {
                setSocial([...draft.social, { platform: 'x', url: '' }])
              }}
            >
              {t('admin.footerContent.addSocial')}
            </Button>
          </div>
        </Stack>

        {/* 法務リンク（Below バー） */}
        <Stack gap="sm">
          <Text as="h3" variant="heading-sm">
            {t('admin.footerContent.legal')}
          </Text>
          <Text muted variant="caption">
            {t('admin.footerContent.legalHint')}
          </Text>
          {draft.legalLinks.map((link, index) => (
            <div key={index} className="flex items-center gap-inline-sm">
              <input
                type="text"
                className={inputClass}
                value={link.label}
                placeholder={t('admin.footerContent.labelPlaceholder')}
                aria-label={t('admin.footerContent.label')}
                onChange={(event) => {
                  const next = [...draft.legalLinks]
                  next[index] = { ...link, label: event.target.value }
                  setLegalLinks(next)
                }}
              />
              <input
                type="text"
                className={inputClass}
                value={link.url}
                placeholder="/privacy"
                aria-label={t('admin.footerContent.url')}
                onChange={(event) => {
                  const next = [...draft.legalLinks]
                  next[index] = { ...link, url: event.target.value }
                  setLegalLinks(next)
                }}
              />
              <Button
                variant="ghost"
                size="sm"
                onClick={() => {
                  setLegalLinks(draft.legalLinks.filter((_, i) => i !== index))
                }}
              >
                {t('common.actions.delete')}
              </Button>
            </div>
          ))}
          <div>
            <Button
              variant="secondary"
              size="sm"
              disabled={disabled}
              onClick={() => {
                setLegalLinks([...draft.legalLinks, { label: '', url: '' }])
              }}
            >
              {t('admin.footerContent.addLegal')}
            </Button>
          </div>
        </Stack>

        {/* Powered by */}
        <label className="flex items-center gap-inline-sm">
          <input
            type="checkbox"
            className="h-4 w-4 accent-accent"
            checked={draft.showPoweredBy}
            onChange={(event) => {
              setShowPoweredBy(event.target.checked)
            }}
          />
          <span className="font-chrome text-caption font-semibold text-text-primary">
            {t('admin.footerContent.showPoweredBy')}
          </span>
        </label>

        <div>
          <Button disabled={disabled || !isDirty} onClick={save}>
            {isSaving ? t('common.actions.saving') : t('common.actions.save')}
          </Button>
        </div>
      </Stack>
    </Card>
  )
}
