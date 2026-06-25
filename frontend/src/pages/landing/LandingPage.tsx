import { useNavigate } from 'react-router-dom'
import { useTranslation } from '@/shared/i18n'
import { Button, NeneMark, Stack, Text } from '@/shared/ui'

/**
 * Default apex landing for the subdomain SaaS (`nene-records.com`). Shipped with
 * every install as a stylish promo for NeNe Records / NENE2 / ayane.co.jp — so an
 * operator who never customizes still gets a presentable top page. Replaceable
 * later with the apex org's own CMS content (see isApex / SpaShellFallback).
 */
export function LandingPage() {
  const navigate = useNavigate()
  const { t } = useTranslation()

  return (
    <div className="relative flex min-h-screen flex-col items-center justify-center bg-surface px-inline-md text-center">
      <Stack gap="lg" className="max-w-xl">
        <div className="flex items-center justify-center gap-inline-sm">
          <NeneMark size={36} className="text-accent" />
          <span className="font-chrome text-heading-sm font-bold tracking-tight text-text-primary">
            NeNe Records
          </span>
        </div>

        <Stack gap="md">
          <Text as="h1" variant="heading-md">
            {t('public.landing.heading')}
          </Text>
          <Text muted>{t('public.landing.subheading')}</Text>
        </Stack>

        <div className="flex justify-center">
          <Button
            className="px-inline-lg"
            onClick={() => {
              void navigate('/signup')
            }}
          >
            {t('public.landing.cta')}
          </Button>
        </div>
      </Stack>

      <footer className="absolute bottom-stack-lg left-0 right-0 px-inline-md">
        <Text variant="caption" muted>
          Powered by{' '}
          <a className="underline hover:text-text-primary" href="https://ayane.co.jp/nene2">
            NENE2
          </a>{' '}
          · Made by{' '}
          <a className="underline hover:text-text-primary" href="https://ayane.co.jp">
            ayane.co.jp
          </a>
        </Text>
      </footer>
    </div>
  )
}
