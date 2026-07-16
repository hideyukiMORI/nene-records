import { Link } from 'react-router-dom'
import { useTranslation } from '@/shared/i18n'
import type { ChapterNav as ChapterNavData } from '@/shared/lib/derive-chapter-nav'

export interface ChapterNavProps {
  nav: ChapterNavData
}

/**
 * Derived 前の章 / 目次 / 次の章 navigation for one chapter of a multi-chapter
 * work, plus a "第{n}章 / 全{total}章" position label. Computed from
 * series/chapter_no/chapter_total — never baked into the body. Uses SPA `Link`s
 * so moving between chapters is a client navigation.
 */
export function ChapterNav({ nav }: ChapterNavProps) {
  const { t } = useTranslation()

  return (
    <nav className="chapter-nav" aria-label={t('public.record.chapterNav.index')}>
      {nav.prevUrl !== null ? (
        <Link viewTransition className="btn btn--ghost" rel="prev" to={nav.prevUrl}>
          ← {t('public.record.chapterNav.prev')}
        </Link>
      ) : null}
      <Link viewTransition className="btn btn--ghost" to={nav.indexUrl}>
        {t('public.record.chapterNav.index')}
      </Link>
      {nav.nextUrl !== null ? (
        <Link viewTransition className="btn btn--ghost" rel="next" to={nav.nextUrl}>
          {t('public.record.chapterNav.next')} →
        </Link>
      ) : null}
      <span className="chapter-nav__pos">
        {t('public.record.chapterNav.position', { n: nav.chapterNo, total: nav.chapterTotal })}
      </span>
    </nav>
  )
}
