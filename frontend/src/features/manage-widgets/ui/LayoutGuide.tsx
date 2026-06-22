import { useState, type ReactNode } from 'react'
import { useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import { Button, Stack, Text } from '@/shared/ui'
import { RelationshipDiagram } from './RelationshipDiagram'

function Overlay({ onClose, children }: { onClose: () => void; children: ReactNode }) {
  const { t } = useTranslation()
  return (
    <div className="fixed inset-0 z-modal flex items-center justify-center p-inline-md">
      <button
        type="button"
        aria-label={t('common.dialog.close')}
        className="absolute inset-0 bg-surface-overlay/80"
        onClick={onClose}
      />
      <div
        role="dialog"
        aria-modal="true"
        className="relative w-full max-w-xl rounded-md border border-border bg-surface-raised p-inline-lg shadow-lg"
      >
        {children}
      </div>
    </div>
  )
}

/** "?" help: explains the menu → menu-widget → region relationship. */
export function HelpModal({ onClose }: { onClose: () => void }) {
  const { t } = useTranslation()
  return (
    <Overlay onClose={onClose}>
      <Stack gap="md">
        <Text as="h2" variant="heading-sm">
          {t('admin.layout.help.title')}
        </Text>
        <RelationshipDiagram />
        <Text muted variant="caption">
          {t('admin.layout.help.body')}
        </Text>
        <div className="flex justify-end">
          <Button onClick={onClose}>{t('admin.layout.help.gotIt')}</Button>
        </div>
      </Stack>
    </Overlay>
  )
}

interface TourStep {
  titleKey: MessageKey
  bodyKey: MessageKey
  diagram?: boolean
}

const TOUR_STEPS: readonly TourStep[] = [
  { titleKey: 'admin.layout.tour.s1Title', bodyKey: 'admin.layout.tour.s1Body' },
  { titleKey: 'admin.layout.tour.s2Title', bodyKey: 'admin.layout.tour.s2Body' },
  { titleKey: 'admin.layout.tour.s3Title', bodyKey: 'admin.layout.tour.s3Body' },
  { titleKey: 'admin.layout.tour.s4Title', bodyKey: 'admin.layout.tour.s4Body', diagram: true },
]

/** First-visit onboarding tour (4 centered steps). */
export function LayoutTour({ onDone }: { onDone: () => void }) {
  const { t } = useTranslation()
  const [i, setI] = useState(0)
  const step = TOUR_STEPS[i]
  const last = i === TOUR_STEPS.length - 1

  if (step === undefined) return null

  return (
    <Overlay onClose={onDone}>
      <Stack gap="md">
        <Text as="span" muted variant="caption">
          {t('admin.layout.tour.stepLabel', { n: String(i + 1), total: String(TOUR_STEPS.length) })}
        </Text>
        <Text as="h2" variant="heading-sm">
          {t(step.titleKey)}
        </Text>
        <Text muted>{t(step.bodyKey)}</Text>
        {step.diagram === true ? <RelationshipDiagram /> : null}
        <div className="flex items-center justify-between gap-inline-md">
          <div className="flex gap-inline-xs">
            {TOUR_STEPS.map((_, k) => (
              <span
                key={k}
                aria-hidden
                className={`h-1.5 w-1.5 rounded-full ${k === i ? 'bg-accent' : 'bg-border'}`}
              />
            ))}
          </div>
          <div className="flex gap-inline-sm">
            {i > 0 ? (
              <Button
                variant="secondary"
                size="sm"
                onClick={() => {
                  setI(i - 1)
                }}
              >
                {t('admin.layout.tour.back')}
              </Button>
            ) : null}
            {last ? (
              <Button
                size="sm"
                onClick={() => {
                  onDone()
                }}
              >
                {t('admin.layout.tour.start')}
              </Button>
            ) : (
              <Button
                size="sm"
                onClick={() => {
                  setI(i + 1)
                }}
              >
                {t('admin.layout.tour.next')}
              </Button>
            )}
          </div>
        </div>
      </Stack>
    </Overlay>
  )
}
