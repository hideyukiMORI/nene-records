import { Link, Navigate, useParams } from 'react-router-dom'
import { currentUserHasCapability } from '@/entities/auth'
import { useMenuList } from '@/entities/menu'
import { ManageMenusView, useManageMenusPage } from '@/features/manage-menus'
import { ManageWidgetsView, useManageWidgetsPage } from '@/features/manage-widgets'
import { useTranslation } from '@/shared/i18n'
import { Stack, Text } from '@/shared/ui'

type AppearanceTab = 'layout' | 'menus'

function LayoutTab() {
  const page = useManageWidgetsPage()
  return (
    <ManageWidgetsView
      widgets={page.widgets}
      entityTypes={page.entityTypes}
      menus={page.menus}
      form={page.form}
      editId={page.editId}
      isSubmitting={page.isSubmitting}
      setField={page.setField}
      resetForm={page.resetForm}
      addToRegion={page.addToRegion}
      editWidget={page.editWidget}
      submit={page.submit}
      remove={page.remove}
    />
  )
}

function MenusTab() {
  const page = useManageMenusPage()
  return <ManageMenusView page={page} />
}

export function AppearanceLayoutPage() {
  const { t } = useTranslation()
  const { tab } = useParams()
  const menusQuery = useMenuList()

  if (!currentUserHasCapability('manage_settings')) {
    return <Navigate to="/forbidden" replace />
  }

  const active: AppearanceTab = tab === 'menus' ? 'menus' : 'layout'

  const tabClass = (isActive: boolean): string =>
    [
      'flex items-center gap-inline-sm border-b-2 px-inline-md py-stack-sm font-chrome text-body',
      isActive
        ? 'border-accent text-text-primary'
        : 'border-transparent text-text-muted hover:text-text-primary',
    ].join(' ')

  return (
    <Stack gap="md">
      <div>
        <Text as="span" muted variant="caption">
          {t('admin.appearance.crumb')} ›{' '}
          {active === 'layout' ? t('admin.appearance.layoutTab') : t('admin.appearance.menusTab')}
        </Text>
        <Text as="h1" variant="heading-md">
          {active === 'layout' ? t('admin.appearance.layoutTab') : t('admin.appearance.menusTab')}
        </Text>
        <Text muted>
          {active === 'layout' ? t('admin.appearance.layoutSub') : t('admin.appearance.menusSub')}
        </Text>
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
      </nav>

      {active === 'layout' ? <LayoutTab /> : <MenusTab />}
    </Stack>
  )
}
