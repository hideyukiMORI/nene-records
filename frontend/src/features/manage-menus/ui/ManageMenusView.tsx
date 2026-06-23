import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import type { NavigationItem } from '@/entities/navigation-item'
import { useTranslation } from '@/shared/i18n'
import { Button, Card, EmptyState, Input, Stack, Text } from '@/shared/ui'
import type { useManageMenusPage } from '../hooks/use-manage-menus-page'

export interface ManageMenusViewProps {
  page: ReturnType<typeof useManageMenusPage>
}

// Master/detail columns. Constant so the arbitrary value is not a className literal.
const MENUS_GRID_CLASS = 'grid grid-cols-1 gap-stack-lg lg:grid-cols-[260px_1fr]'

/**
 * Menu name field with a local draft so renaming commits on blur / Enter rather
 * than firing a PATCH on every keystroke (which also corrupted the input as the
 * refetched server value overwrote it mid-type). Reset via `key={menu.id}`.
 */
function MenuNameEditor({
  name,
  label,
  onRename,
}: {
  name: string
  label: string
  onRename: (name: string) => void
}) {
  const [draft, setDraft] = useState(name)
  const commit = () => {
    const next = draft.trim()
    if (next === '') {
      setDraft(name)
    } else if (next !== name) {
      onRename(next)
    }
  }
  return (
    <Input
      id="menu-name"
      label={label}
      value={draft}
      onChange={(e) => {
        setDraft(e.target.value)
      }}
      onBlur={commit}
      onKeyDown={(e) => {
        if (e.key === 'Enter') {
          e.currentTarget.blur()
        }
      }}
    />
  )
}

export function ManageMenusView({ page }: ManageMenusViewProps) {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [draftLabel, setDraftLabel] = useState('')
  const [draftUrl, setDraftUrl] = useState('')
  const [editing, setEditing] = useState<NavigationItem | null>(null)

  const { menus, activeMenu, activeItems } = page

  const submitAdd = () => {
    if (draftLabel.trim() === '') return
    void page.addItem(draftLabel.trim(), draftUrl.trim())
    setDraftLabel('')
    setDraftUrl('')
  }

  const placed = activeMenu === null ? 0 : page.placementCount(activeMenu.id)

  return (
    <div className={MENUS_GRID_CLASS}>
      {/* master: menu list */}
      <Stack gap="sm">
        <div className="flex items-center justify-between">
          <Text as="h2" variant="heading-sm">
            {t('admin.menus.listTitle')}
          </Text>
          <Button
            size="sm"
            onClick={() => {
              void page.addMenu(t('admin.menus.newMenuName'))
            }}
          >
            {t('admin.menus.new')}
          </Button>
        </div>
        {menus.length === 0 ? (
          <Text muted variant="caption">
            {t('admin.menus.empty')}
          </Text>
        ) : (
          <ul className="flex flex-col gap-stack-xs">
            {menus.map((menu) => (
              <li key={menu.id}>
                <button
                  type="button"
                  onClick={() => {
                    page.select(menu.id)
                    setEditing(null)
                  }}
                  className={[
                    'flex w-full items-center justify-between gap-inline-sm rounded-md border px-inline-md py-stack-sm text-left',
                    menu.id === page.selectedId
                      ? 'border-accent bg-accent-weak'
                      : 'border-border bg-surface-raised hover:bg-surface-overlay',
                  ].join(' ')}
                >
                  <span className="flex flex-col">
                    <span className="font-medium">{menu.name}</span>
                    <span className="text-caption text-text-muted">/{menu.slug}</span>
                  </span>
                  {page.placementCount(menu.id) > 0 ? (
                    <span className="rounded-full border border-border px-inline-sm text-caption text-text-muted">
                      {t('admin.menus.placed')}
                    </span>
                  ) : null}
                </button>
              </li>
            ))}
          </ul>
        )}
      </Stack>

      {/* detail */}
      {activeMenu === null ? (
        <Card>
          <EmptyState
            title={t('admin.menus.detail.noneTitle')}
            description={t('admin.menus.detail.noneDescription')}
          />
        </Card>
      ) : (
        <Stack gap="md">
          <Card className="flex flex-col gap-stack-sm">
            <div className="flex items-end justify-between gap-inline-md">
              <div className="flex-1">
                <MenuNameEditor
                  key={activeMenu.id}
                  name={activeMenu.name}
                  label={t('admin.menus.nameLabel')}
                  onRename={(name) => {
                    void page.renameMenu(name)
                  }}
                />
                <Text as="span" muted variant="caption">
                  /{activeMenu.slug}
                </Text>
              </div>
              <Button
                variant="danger"
                size="sm"
                onClick={() => {
                  void page.removeMenu()
                }}
              >
                {t('common.actions.delete')}
              </Button>
            </div>

            {/* placement callout */}
            {placed > 0 ? (
              <div className="flex items-center justify-between gap-inline-md rounded-md border border-border bg-surface-overlay px-inline-md py-stack-sm">
                <Text as="span" variant="caption">
                  {t('admin.menus.placedCallout', { count: String(placed) })}
                </Text>
                <Button
                  variant="secondary"
                  size="sm"
                  onClick={() => {
                    void navigate('/admin/appearance/layout')
                  }}
                >
                  {t('admin.menus.viewInLayout')}
                </Button>
              </div>
            ) : (
              <div className="flex items-center justify-between gap-inline-md rounded-md border border-border bg-accent-weak px-inline-md py-stack-sm">
                <Text as="span" variant="caption">
                  {t('admin.menus.unplacedCallout')}
                </Text>
                <Button
                  size="sm"
                  onClick={() => {
                    void page
                      .placeMenu(activeMenu.id)
                      .then(() => navigate('/admin/appearance/layout'))
                  }}
                >
                  {t('admin.menus.placeInRegion')}
                </Button>
              </div>
            )}
          </Card>

          {/* items */}
          <Stack gap="sm">
            <Text as="h2" variant="heading-sm">
              {t('admin.menus.itemsTitle', { count: String(activeItems.length) })}
            </Text>
            {activeItems.length === 0 ? (
              <Text muted variant="caption">
                {t('admin.menus.itemsEmpty')}
              </Text>
            ) : (
              <ul className="flex flex-col gap-stack-xs">
                {activeItems.map((item, index) =>
                  editing?.id === item.id ? (
                    <Card as="li" key={item.id} className="flex flex-col gap-stack-sm">
                      <Input
                        id={`menu-item-label-${String(item.id)}`}
                        label={t('admin.menus.itemLabel')}
                        value={editing.label}
                        onChange={(e) => {
                          setEditing({ ...editing, label: e.target.value })
                        }}
                      />
                      <Input
                        id={`menu-item-url-${String(item.id)}`}
                        label={t('admin.menus.itemUrl')}
                        value={editing.url}
                        onChange={(e) => {
                          setEditing({ ...editing, url: e.target.value })
                        }}
                      />
                      <div className="flex gap-inline-sm">
                        <Button
                          size="sm"
                          onClick={() => {
                            void page.editItem(item, editing.label, editing.url)
                            setEditing(null)
                          }}
                        >
                          {t('common.actions.save')}
                        </Button>
                        <Button
                          variant="secondary"
                          size="sm"
                          onClick={() => {
                            setEditing(null)
                          }}
                        >
                          {t('common.actions.cancel')}
                        </Button>
                      </div>
                    </Card>
                  ) : (
                    <Card
                      as="li"
                      key={item.id}
                      padding="row"
                      className="flex items-center justify-between gap-inline-sm"
                    >
                      <span className="flex flex-col">
                        <span className="font-medium">{item.label}</span>
                        <span className="text-caption text-text-muted">{item.url}</span>
                      </span>
                      <div className="flex items-center gap-inline-sm">
                        <Button
                          variant="secondary"
                          size="sm"
                          disabled={index === 0}
                          onClick={() => {
                            void page.moveItem(index, -1)
                          }}
                        >
                          ↑
                        </Button>
                        <Button
                          variant="secondary"
                          size="sm"
                          disabled={index === activeItems.length - 1}
                          onClick={() => {
                            void page.moveItem(index, 1)
                          }}
                        >
                          ↓
                        </Button>
                        <Button
                          variant="secondary"
                          size="sm"
                          onClick={() => {
                            setEditing(item)
                          }}
                        >
                          {t('common.actions.edit')}
                        </Button>
                        <Button
                          variant="danger"
                          size="sm"
                          onClick={() => {
                            void page.removeItem(item.id)
                          }}
                        >
                          {t('common.actions.delete')}
                        </Button>
                      </div>
                    </Card>
                  ),
                )}
              </ul>
            )}

            {/* add item form */}
            <Card className="flex flex-col gap-stack-sm">
              <Text as="span" variant="caption" muted>
                {t('admin.menus.addItem')}
              </Text>
              <Input
                id="menu-item-new-label"
                label={t('admin.menus.itemLabel')}
                placeholder={t('admin.menus.itemLabelPlaceholder')}
                value={draftLabel}
                onChange={(e) => {
                  setDraftLabel(e.target.value)
                }}
              />
              <Input
                id="menu-item-new-url"
                label={t('admin.menus.itemUrl')}
                placeholder="/releases"
                value={draftUrl}
                onChange={(e) => {
                  setDraftUrl(e.target.value)
                }}
              />
              <div>
                <Button onClick={submitAdd}>{t('admin.menus.addItem')}</Button>
              </div>
            </Card>
          </Stack>
        </Stack>
      )}
    </div>
  )
}
