import { useState } from 'react'
import { useTranslation } from '@/shared/i18n'
import {
  applyConsent,
  type ConsentChoice,
  readConsentChoice,
  storeConsentChoice,
  type WebAnalyticsClientConfig,
} from '@/shared/lib/web-analytics'
import { Button } from '@/shared/ui'

interface ConsentBannerProps {
  config: WebAnalyticsClientConfig
}

/**
 * GDPR-style consent prompt for the public site. Shown only when analytics is
 * enabled, the configured default is the EU-safe `denied`, and the visitor has
 * not decided yet — a `granted` default means the admin opted out of the prompt.
 * The choice persists in localStorage and is pushed to the tag via Consent Mode
 * v2 `update`.
 */
export function ConsentBanner({ config }: ConsentBannerProps) {
  const { t } = useTranslation()
  const [choice, setChoice] = useState<ConsentChoice | null>(() => readConsentChoice())

  if (!config.enabled || config.consentDefault === 'granted' || choice !== null) {
    return null
  }

  const decide = (granted: boolean): void => {
    const next: ConsentChoice = granted ? 'granted' : 'denied'
    applyConsent(next)
    storeConsentChoice(next)
    setChoice(next)
  }

  return (
    <div
      role="dialog"
      aria-label={t('public.consent.label')}
      className="fixed inset-x-0 bottom-0 z-50 border-t border-border bg-surface shadow-lg"
    >
      <div className="mx-auto flex max-w-3xl flex-col gap-stack-sm px-inline-md py-stack-md sm:flex-row sm:items-center sm:justify-between">
        <p className="font-sans text-body text-text-primary">{t('public.consent.message')}</p>
        <div className="flex shrink-0 gap-inline-sm">
          <Button
            variant="subtle"
            size="sm"
            onClick={() => {
              decide(false)
            }}
          >
            {t('public.consent.decline')}
          </Button>
          <Button
            variant="primary"
            size="sm"
            onClick={() => {
              decide(true)
            }}
          >
            {t('public.consent.accept')}
          </Button>
        </div>
      </div>
    </div>
  )
}
