import { useEffect, useState } from 'react'
import { Link, Navigate, useParams } from 'react-router-dom'
import { currentUserHasCapability } from '@/entities/auth'
import { useMenuList } from '@/entities/menu'
import {
  ThemeWorkspace,
  useFooterConfigPage,
  useHeaderConfigPage,
  useHomeHeroPage,
  usePublicThemePage,
  useThemeCustomizePage,
} from '@/features/manage-appearance'
import { ManageMenusView, useManageMenusPage } from '@/features/manage-menus'
import {
  HelpModal,
  LayoutTour,
  ManageWidgetsView,
  useManageWidgetsPage,
} from '@/features/manage-widgets'
import { useTranslation } from '@/shared/i18n'
import { setChromeRail } from '@/shared/lib/chrome-rail'
import { useUnsavedChangesGuard } from '@/shared/lib/use-unsaved-changes-guard'
import { Button, ConfirmDialog, Stack, Text } from '@/shared/ui'

const TOUR_LS_KEY = 'nene_layout_tour_seen'

type AppearanceTab = 'layout' | 'menus' | 'theme'

function LayoutTab() {
  const page = useManageWidgetsPage()
  return <ManageWidgetsView page={page} />
}

function MenusTab() {
  const page = useManageMenusPage()
  return <ManageMenusView page={page} />
}

function ThemeTab() {
  const { t } = useTranslation()
  const page = usePublicThemePage()
  const customize = useThemeCustomizePage()
  const headerContent = useHeaderConfigPage()
  const footerContent = useFooterConfigPage()
  const homeHero = useHomeHeroPage()
  // Warn before leaving with unsaved customizer edits (route change + tab close).
  const blocker = useUnsavedChangesGuard(customize.isDirty)
  return (
    <Stack gap="lg">
      <ThemeWorkspace
        pick={page}
        customize={customize}
        header={headerContent}
        footer={footerContent}
        hero={homeHero}
      />

      {blocker.state === 'blocked' ? (
        <ConfirmDialog
          open
          title={t('admin.themeCustomize.unsavedTitle')}
          description={t('admin.themeCustomize.unsavedBody')}
          confirmLabel={t('admin.themeCustomize.unsavedLeave')}
          cancelLabel={t('admin.themeCustomize.unsavedStay')}
          onConfirm={() => {
            blocker.proceed()
          }}
          onCancel={() => {
            blocker.reset()
          }}
        />
      ) : null}
    </Stack>
  )
}

export function AppearanceLayoutPage() {
  const { t } = useTranslation()
  const { tab } = useParams()
  const menusQuery = useMenuList()
  const [help, setHelp] = useState(false)
  const [tourOn, setTourOn] = useState(() => localStorage.getItem(TOUR_LS_KEY) === null)

  const active: AppearanceTab = tab === 'menus' ? 'menus' : tab === 'theme' ? 'theme' : 'layout'

  // The theme workspace (#787) and the layout builder are wide multi-pane
  // surfaces: collapse the app sidebar into an icon rail while they are shown
  // (#789). Desktop-only gating lives in AppShell; menus stays full-width nav.
  useEffect(() => {
    setChromeRail(active === 'theme' || active === 'layout')
    return () => {
      setChromeRail(false)
    }
  }, [active])

  if (!currentUserHasCapability('manage_settings')) {
    return <Navigate to="/forbidden" replace />
  }

  const endTour = () => {
    setTourOn(false)
    localStorage.setItem(TOUR_LS_KEY, '1')
  }

  const tabTitle: Record<AppearanceTab, string> = {
    layout: t('admin.appearance.layoutTab'),
    menus: t('admin.appearance.menusTab'),
    theme: t('admin.appearance.themeTab'),
  }
  const tabSub: Record<AppearanceTab, string> = {
    layout: t('admin.appearance.layoutSub'),
    menus: t('admin.appearance.menusSub'),
    theme: t('admin.appearance.themeSub'),
  }

  const tabClass = (isActive: boolean): string =>
    [
      'flex items-center gap-inline-sm border-b-2 px-inline-md py-stack-sm font-chrome text-body',
      isActive
        ? 'border-accent text-text-primary'
        : 'border-transparent text-text-muted hover:text-text-primary',
    ].join(' ')

  return (
    <Stack gap="md">
      <div className="flex items-start justify-between gap-inline-md">
        <div>
          <Text as="span" muted variant="caption">
            {t('admin.appearance.crumb')} › {tabTitle[active]}
          </Text>
          <Text as="h1" variant="heading-md">
            {tabTitle[active]}
          </Text>
          <Text muted>{tabSub[active]}</Text>
        </div>
        <Button
          variant="secondary"
          size="sm"
          onClick={() => {
            setHelp(true)
          }}
        >
          {t('admin.layout.helpButton')}
        </Button>
      </div>

      <nav className="flex items-center gap-inline-md border-b border-border">
        <Link to="/admin/appearance/layout" className={tabClass(active === 'layout')}>
          {t('admin.appearance.layoutTab')}
        </Link>
        <Link to="/admin/appearance/menus" className={tabClass(active === 'menus')}>
          {t('admin.appearance.menusTab')}
          <span className="rounded-full border border-border px-inline-sm text-caption">
            {menusQuery.data?.items.length ?? 0}
          </span>
        </Link>
        <Link to="/admin/appearance/theme" className={tabClass(active === 'theme')}>
          {t('admin.appearance.themeTab')}
        </Link>
      </nav>

      {active === 'layout' ? <LayoutTab /> : active === 'menus' ? <MenusTab /> : <ThemeTab />}

      {help ? (
        <HelpModal
          onClose={() => {
            setHelp(false)
          }}
        />
      ) : null}
      {tourOn ? <LayoutTour onDone={endTour} /> : null}
    </Stack>
  )
}
