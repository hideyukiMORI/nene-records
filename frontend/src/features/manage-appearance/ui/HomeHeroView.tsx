import { BlocksFieldEditor } from '@/features/edit-entity-text-fields'
import { useTranslation } from '@/shared/i18n'
import { Button, LoadingState, Stack, Text } from '@/shared/ui'
import type { HomeHeroPageState } from '../hooks/useHomeHeroPage'

const HERO_ONLY = ['hero'] as const

/**
 * Home masthead editor: a single hero block edited with the reused typed-block
 * editor (#486), persisted to the `home_hero` setting. Empty → the public home
 * falls back to the auto stats-hero.
 */
export function HomeHeroView({
  draft,
  setDraft,
  save,
  isLoading,
  isSaving,
  isDirty,
}: HomeHeroPageState) {
  const { t } = useTranslation()

  if (isLoading) {
    return <LoadingState>{t('admin.homeHero.loading')}</LoadingState>
  }

  return (
    <Stack gap="sm">
      <Text as="h2" variant="heading-sm">
        {t('admin.homeHero.title')}
      </Text>
      <Text muted>{t('admin.homeHero.description')}</Text>
      <BlocksFieldEditor
        id="home-hero"
        label={t('admin.homeHero.fieldLabel')}
        value={draft}
        disabled={isSaving}
        allowedTypes={HERO_ONLY}
        onChange={setDraft}
      />
      <div>
        <Button onClick={save} disabled={!isDirty || isSaving}>
          {isSaving ? t('admin.homeHero.saving') : t('admin.homeHero.save')}
        </Button>
      </div>
    </Stack>
  )
}
