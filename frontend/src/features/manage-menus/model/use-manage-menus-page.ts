import { useCallback, useMemo, useState } from 'react'
import {
  useCreateMenu,
  useDeleteMenu,
  useMenuList,
  useUpdateMenu,
  type Menu,
} from '@/entities/menu'
import {
  useCreateNavigationItem,
  useDeleteNavigationItem,
  useNavigationItemList,
  useUpdateNavigationItem,
  type NavigationItem,
} from '@/entities/navigation-item'
import { useCreateWidget, useWidgetList } from '@/entities/widget'
import { useTranslation } from '@/shared/i18n'
import { useToast } from '@/shared/ui'

/** Prefer the server's validation message over the generic fallback. */
function serverMessage(error: unknown, fallback: string): string {
  if (typeof error === 'object' && error !== null) {
    const e = error as {
      errors?: ReadonlyArray<{ message?: string }>
      detail?: string
      title?: string
    }
    return e.errors?.[0]?.message ?? e.detail ?? e.title ?? fallback
  }
  return fallback
}

/**
 * Menu management (master–detail): named menus on the left, the selected menu's
 * link items on the right. Items belong to a menu via `menuId`. A menu is shown
 * publicly only through a `menu` widget that references it — surfaced as a
 * placement count + a "place in a region" action.
 */
export function useManageMenusPage() {
  const menusQuery = useMenuList()
  const itemsQuery = useNavigationItemList()
  const widgetsQuery = useWidgetList()

  const createMenu = useCreateMenu()
  const updateMenu = useUpdateMenu()
  const deleteMenu = useDeleteMenu()
  const createItem = useCreateNavigationItem()
  const updateItem = useUpdateNavigationItem()
  const deleteItem = useDeleteNavigationItem()
  const createWidget = useCreateWidget()
  const { showToast } = useToast()
  const { t } = useTranslation()

  const [selectedId, setSelectedId] = useState<number | null>(null)

  const reportError = useCallback(
    (error: unknown) => {
      showToast(serverMessage(error, t('admin.menus.toast.error')), 'error')
    },
    [showToast, t],
  )

  const menus = useMemo(() => menusQuery.data?.items ?? [], [menusQuery.data?.items])
  const allItems = useMemo(() => itemsQuery.data?.items ?? [], [itemsQuery.data?.items])
  const widgets = useMemo(() => widgetsQuery.data?.items ?? [], [widgetsQuery.data?.items])

  const activeMenu: Menu | null = menus.find((m) => m.id === selectedId) ?? menus[0] ?? null

  const activeItems = useMemo(
    () =>
      allItems
        .filter((item) => item.menuId === (activeMenu?.id ?? -1))
        .sort((a, b) => a.displayOrder - b.displayOrder || a.id - b.id),
    [allItems, activeMenu?.id],
  )

  const placementCount = useCallback(
    (menuId: number): number =>
      widgets.filter((w) => w.widgetType === 'menu' && w.settings['menuId'] === menuId).length,
    [widgets],
  )

  const addMenu = useCallback(
    async (name: string) => {
      try {
        const menu = await createMenu.mutateAsync({ name })
        setSelectedId(menu.id)
        showToast(t('admin.menus.toast.created'))
      } catch (error) {
        reportError(error)
      }
    },
    [createMenu, showToast, t, reportError],
  )

  const renameMenu = useCallback(
    async (name: string) => {
      if (activeMenu === null) return
      try {
        await updateMenu.mutateAsync({ id: activeMenu.id, input: { name } })
      } catch (error) {
        reportError(error)
      }
    },
    [activeMenu, updateMenu, reportError],
  )

  const removeMenu = useCallback(async () => {
    if (activeMenu === null) return
    try {
      await deleteMenu.mutateAsync(activeMenu.id)
      setSelectedId(null)
      showToast(t('admin.menus.toast.deleted'))
    } catch (error) {
      reportError(error)
    }
  }, [activeMenu, deleteMenu, showToast, t, reportError])

  const addItem = useCallback(
    async (label: string, url: string) => {
      if (activeMenu === null) return
      const last = activeItems[activeItems.length - 1]
      const nextOrder = last !== undefined ? last.displayOrder + 1 : 0
      try {
        await createItem.mutateAsync({
          label,
          url: url.trim() === '' ? '/' : url,
          menuId: activeMenu.id,
          displayOrder: nextOrder,
        })
      } catch (error) {
        reportError(error)
      }
    },
    [activeMenu, activeItems, createItem, reportError],
  )

  const editItem = useCallback(
    async (item: NavigationItem, label: string, url: string) => {
      try {
        await updateItem.mutateAsync({
          id: item.id,
          input: {
            label,
            url,
            menuId: item.menuId,
            displayOrder: item.displayOrder,
          },
        })
      } catch (error) {
        reportError(error)
      }
    },
    [updateItem, reportError],
  )

  const removeItem = useCallback(
    async (id: number) => {
      try {
        await deleteItem.mutateAsync(id)
      } catch (error) {
        reportError(error)
      }
    },
    [deleteItem, reportError],
  )

  // Reorder by swapping display_order with the adjacent item.
  const moveItem = useCallback(
    async (index: number, dir: -1 | 1) => {
      const target = index + dir
      if (target < 0 || target >= activeItems.length) return
      const a = activeItems[index]
      const b = activeItems[target]
      if (a === undefined || b === undefined) return
      try {
        await Promise.all([
          updateItem.mutateAsync({
            id: a.id,
            input: {
              label: a.label,
              url: a.url,
              menuId: a.menuId,
              displayOrder: b.displayOrder,
            },
          }),
          updateItem.mutateAsync({
            id: b.id,
            input: {
              label: b.label,
              url: b.url,
              menuId: b.menuId,
              displayOrder: a.displayOrder,
            },
          }),
        ])
      } catch (error) {
        reportError(error)
      }
    },
    [activeItems, updateItem, reportError],
  )

  // Create a sidebar menu widget for this menu (the "place in a region" action).
  const placeMenu = useCallback(
    async (menuId: number) => {
      try {
        await createWidget.mutateAsync({
          widgetType: 'menu',
          region: 'sidebar',
          displayOrder: 0,
          title: null,
          settings: { menuId },
        })
      } catch (error) {
        reportError(error)
      }
    },
    [createWidget, reportError],
  )

  return {
    menus,
    activeMenu,
    activeItems,
    selectedId: activeMenu?.id ?? null,
    isLoading: menusQuery.isLoading,
    isError: menusQuery.isError || itemsQuery.isError,
    errorTitle: menusQuery.error?.title ?? itemsQuery.error?.title ?? null,
    select: setSelectedId,
    placementCount,
    addMenu,
    renameMenu,
    removeMenu,
    addItem,
    editItem,
    removeItem,
    moveItem,
    placeMenu,
    isSavingMenu: createMenu.isPending || updateMenu.isPending || deleteMenu.isPending,
    refetch: async () => {
      await Promise.all([menusQuery.refetch(), itemsQuery.refetch(), widgetsQuery.refetch()])
    },
  }
}
