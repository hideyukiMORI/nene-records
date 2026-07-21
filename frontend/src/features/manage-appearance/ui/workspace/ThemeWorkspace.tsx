import { useMemo, useState } from 'react'
import './theme-workspace.css'
import { useMediaList } from '@/entities/media'
import { useTranslation, type MessageKey } from '@/shared/i18n'
import { resolveDraftImageUrls, type ThemeImages } from '@/shared/lib/theme-customization'
import { THEME_PREVIEW_PARAM } from '@/shared/lib/theme-preview-protocol'
import { Button, Text } from '@/shared/ui'
import type { FloatingCtaPageState } from '../../hooks/useFloatingCtaPage'
import type { FooterConfigPageState } from '../../hooks/useFooterConfigPage'
import type { HeaderConfigPageState } from '../../hooks/useHeaderConfigPage'
import type { HomeHeroPageState } from '../../hooks/useHomeHeroPage'
import type { PublicThemePageState } from '../../hooks/usePublicThemePage'
import type { ThemeCustomizePageState } from '../../hooks/useThemeCustomizePage'
import { useThemePreviewSender } from '../../hooks/useThemePreviewSender'
import { FloatingCtaView } from '../FloatingCtaView'
import { FooterContentView } from '../FooterContentView'
import { HeaderContentView } from '../HeaderContentView'
import { HeaderPreview } from '../HeaderPreview'
import { HomeHeroView } from '../HomeHeroView'
import { PublicThemeView } from '../PublicThemeView'
import {
  AdvancedPanel,
  BrandPanel,
  HeaderAppearanceDisclosure,
  LayoutPanel,
  PanelHead,
  ScopeNote,
  ScopeTag,
  TypographyPanel,
  type ImageSlotHelpers,
} from './CustomizePanels'

type SectionId =
  | 'theme'
  | 'brand'
  | 'type'
  | 'layout'
  | 'header'
  | 'advanced'
  | 'footer'
  | 'hero'
  | 'floatingCta'

export interface ThemeWorkspaceProps {
  pick: PublicThemePageState
  customize: ThemeCustomizePageState
  header: HeaderConfigPageState
  footer: FooterConfigPageState
  hero: HomeHeroPageState
  floatingCta: FloatingCtaPageState
}

interface NavItem {
  id: SectionId
  labelKey: MessageKey
  dirty: boolean
}

interface NavGroup {
  labelKey: MessageKey
  tag: 'instant' | 'overrides' | 'independent'
  tagKey: MessageKey
  hintKey?: MessageKey
  items: NavItem[]
}

const IMAGE_SLOTS: ReadonlyArray<keyof ThemeImages> = ['logo', 'hero', 'background']

/**
 * The redesigned theme tab (#787, ClaudeDesign IA handoff): a 3-pane workspace
 * — section nav (left, grouped by save scope), one panel at a time (centre),
 * always-on live preview (right; slide-over + FAB under 1040px). The customize
 * group shares ONE theme_overrides draft across its panels; a fixed bottom save
 * bar appears while that draft is dirty. Content sections (header_config /
 * footer_config / home_hero) keep their own in-place save buttons.
 */
export function ThemeWorkspace({
  pick,
  customize,
  header,
  footer,
  hero,
  floatingCta,
}: ThemeWorkspaceProps) {
  const { t } = useTranslation()
  const [section, setSection] = useState<SectionId>('theme')
  const [previewOpen, setPreviewOpen] = useState(false)
  const [previewMobile, setPreviewMobile] = useState(false)

  // Media id → url map for image slot thumbnails + preview draft resolution.
  const mediaList = useMediaList()
  const idToUrl = useMemo(() => {
    const map = new Map<number, string>()
    for (const item of mediaList.data?.items ?? []) {
      map.set(item.id, item.url)
    }
    return map
  }, [mediaList.data?.items])

  const images: ImageSlotHelpers = {
    thumbUrl: (slot, mode) => {
      const value = customize.draft.images?.[slot]?.[mode]
      if (typeof value === 'number') return idToUrl.get(value)
      return typeof value === 'string' ? value : undefined
    },
    setImage: (slot, mode, id) => {
      // Rebuild the slot map, pruning modes/slots that become empty (so storage
      // and the exactOptional types stay clean without dynamic `delete`).
      const nextImages: ThemeImages = {}
      for (const key of IMAGE_SLOTS) {
        const base = customize.draft.images?.[key]
        const light = key === slot && mode === 'light' ? id : base?.light
        const dark = key === slot && mode === 'dark' ? id : base?.dark
        if (light !== undefined || dark !== undefined) {
          nextImages[key] = { light, dark }
        }
      }
      customize.setKnob('images', Object.keys(nextImages).length > 0 ? nextImages : undefined)
    },
  }

  // Always-on live preview (#538): the public /search page in an iframe, the
  // draft pushed to it via postMessage (image ids resolved to URLs first).
  const previewDraft = useMemo(
    () => resolveDraftImageUrls(customize.draft, (id) => idToUrl.get(id)),
    [customize.draft, idToUrl],
  )
  const { iframeRef } = useThemePreviewSender(customize.themeId, previewDraft)

  const groups: NavGroup[] = [
    {
      labelKey: 'admin.themeWs.groupAppearance',
      tag: 'instant',
      tagKey: 'admin.themeWs.tagInstant',
      items: [{ id: 'theme', labelKey: 'admin.themeWs.navTheme', dirty: false }],
    },
    {
      labelKey: 'admin.themeWs.groupCustomize',
      tag: 'overrides',
      tagKey: 'admin.themeWs.tagOverrides',
      hintKey: 'admin.themeWs.groupCustomizeHint',
      items: [
        { id: 'brand', labelKey: 'admin.themeWs.navBrand', dirty: customize.isDirty },
        { id: 'type', labelKey: 'admin.themeWs.navType', dirty: customize.isDirty },
        { id: 'layout', labelKey: 'admin.themeWs.navLayout', dirty: customize.isDirty },
        { id: 'header', labelKey: 'admin.themeWs.navHeader', dirty: header.isDirty },
        { id: 'advanced', labelKey: 'admin.themeWs.navAdvanced', dirty: customize.isDirty },
      ],
    },
    {
      labelKey: 'admin.themeWs.groupContent',
      tag: 'independent',
      tagKey: 'admin.themeWs.tagIndependent',
      items: [
        { id: 'footer', labelKey: 'admin.themeWs.navFooter', dirty: footer.isDirty },
        { id: 'hero', labelKey: 'admin.themeWs.navHero', dirty: hero.isDirty },
        { id: 'floatingCta', labelKey: 'admin.themeWs.navFloatingCta', dirty: floatingCta.isDirty },
      ],
    },
  ]

  return (
    <>
      <div className="theme-ws">
        {/* left: section nav = the save-model map */}
        <nav className="ws-nav" aria-label={t('admin.themeWs.navAria')}>
          {groups.map((group) => (
            <div key={group.labelKey} className="ws-nav-group">
              <div className="ws-nav-group-label">
                {t(group.labelKey)}
                <ScopeTag kind={group.tag} labelKey={group.tagKey} />
              </div>
              {group.hintKey !== undefined ? (
                <div className="ws-nav-group-hint">{t(group.hintKey)}</div>
              ) : null}
              {group.items.map((item) => (
                <button
                  key={item.id}
                  type="button"
                  className="ws-nav-item"
                  aria-current={section === item.id}
                  onClick={() => {
                    setSection(item.id)
                  }}
                >
                  <span className="lbl">{t(item.labelKey)}</span>
                  <span
                    className={item.dirty ? 'ws-dirty-dot on' : 'ws-dirty-dot'}
                    aria-hidden="true"
                  />
                </button>
              ))}
            </div>
          ))}
        </nav>

        {/* centre: the active panel */}
        <div className="ws-content">
          <div className="ws-panel" key={section}>
            {section === 'theme' ? (
              <div>
                <PanelHead
                  title={t('admin.themeWs.navTheme')}
                  desc={t('admin.themeWs.descTheme')}
                />
                <PublicThemeView {...pick} />
              </div>
            ) : null}
            {section === 'brand' ? <BrandPanel customize={customize} images={images} /> : null}
            {section === 'type' ? <TypographyPanel customize={customize} /> : null}
            {section === 'layout' ? <LayoutPanel customize={customize} /> : null}
            {section === 'header' ? (
              <div>
                <PanelHead
                  title={t('admin.themeWs.navHeader')}
                  desc={t('admin.themeWs.descHeader')}
                />
                <HeaderPreview flags={customize.draft.flags} header={header.draft} />
                <div className="mb-4">
                  <div className="mb-2 flex items-center gap-inline-sm">
                    <Text as="h3" variant="heading-sm">
                      {t('admin.themeWs.cardHeaderContent')}
                    </Text>
                    <ScopeTag kind="independent" labelKey="admin.themeWs.tagInplace" />
                  </div>
                  <HeaderContentView {...header} />
                </div>
                <HeaderAppearanceDisclosure customize={customize} />
              </div>
            ) : null}
            {section === 'advanced' ? <AdvancedPanel customize={customize} /> : null}
            {section === 'footer' ? (
              <div>
                <PanelHead
                  title={t('admin.themeWs.navFooter')}
                  desc={t('admin.themeWs.descFooter')}
                />
                <ScopeNote
                  kind="independent"
                  tagKey="admin.themeWs.tagInplace"
                  noteKey="admin.themeWs.scopeInplaceNote"
                />
                <FooterContentView {...footer} />
              </div>
            ) : null}
            {section === 'hero' ? (
              <div>
                <ScopeNote
                  kind="independent"
                  tagKey="admin.themeWs.tagInplace"
                  noteKey="admin.themeWs.scopeInplaceNote"
                />
                <HomeHeroView {...hero} />
              </div>
            ) : null}
            {section === 'floatingCta' ? (
              <div>
                <PanelHead
                  title={t('admin.themeWs.navFloatingCta')}
                  desc={t('admin.themeWs.descFloatingCta')}
                />
                <ScopeNote
                  kind="independent"
                  tagKey="admin.themeWs.tagInplace"
                  noteKey="admin.themeWs.scopeInplaceNote"
                />
                <FloatingCtaView {...floatingCta} />
              </div>
            ) : null}
          </div>
        </div>

        {/* right: always-on live preview (slide-over below 1040px) */}
        <aside
          className={previewOpen ? 'ws-preview open' : 'ws-preview'}
          aria-label={t('admin.themeCustomize.preview.title')}
        >
          <div className="ws-pv-head">
            <Text as="h3" variant="heading-sm">
              {t('admin.themeCustomize.preview.title')}
            </Text>
            <span className="ws-pv-live">
              <span className="dot" aria-hidden="true" />
              {t('admin.themeWs.pvLive')}
            </span>
          </div>
          <div className="flex items-center justify-end gap-inline-sm">
            <Button
              variant={previewMobile ? 'ghost' : 'secondary'}
              size="sm"
              aria-pressed={!previewMobile}
              onClick={() => {
                setPreviewMobile(false)
              }}
            >
              {t('admin.themeWs.pvDesktop')}
            </Button>
            <Button
              variant={previewMobile ? 'secondary' : 'ghost'}
              size="sm"
              aria-pressed={previewMobile}
              onClick={() => {
                setPreviewMobile(true)
              }}
            >
              {t('admin.themeWs.pvMobile')}
            </Button>
          </div>
          <div className={previewMobile ? 'ws-pv-frame mobile' : 'ws-pv-frame'}>
            <iframe
              ref={iframeRef}
              src={`/search?${THEME_PREVIEW_PARAM}=1`}
              title={t('admin.themeCustomize.preview.title')}
            />
          </div>
          <p className="ws-pv-note">{t('admin.themeWs.pvNote')}</p>
        </aside>

        {previewOpen ? (
          <div
            className="theme-ws-backdrop show"
            aria-hidden="true"
            onClick={() => {
              setPreviewOpen(false)
            }}
          />
        ) : null}
      </div>

      {/* preview FAB (shown by CSS below 1040px) */}
      <button
        type="button"
        className="theme-ws-fab"
        onClick={() => {
          setPreviewOpen((prev) => !prev)
        }}
      >
        {t('admin.themeWs.pvFab')}
      </button>

      {/* fixed save bar: the single save point for the theme_overrides draft */}
      <div
        className={customize.isDirty ? 'theme-ws-savebar show' : 'theme-ws-savebar'}
        role="status"
      >
        <div className="sb-text">
          <span>{t('admin.themeWs.sbTitle')}</span>
          <span className="sb-sub">{t('admin.themeWs.sbSub')}</span>
        </div>
        <div className="sb-actions">
          <Button variant="ghost" disabled={customize.isSaving} onClick={customize.reset}>
            {t('admin.themeCustomize.reset')}
          </Button>
          <Button disabled={customize.isSaving} onClick={customize.save}>
            {customize.isSaving ? t('admin.themeCustomize.saving') : t('admin.themeWs.sbSave')}
          </Button>
        </div>
      </div>
    </>
  )
}
