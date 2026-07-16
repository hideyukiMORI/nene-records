import { Link } from 'react-router-dom'
import { useTranslation } from '@/shared/i18n'
import type { PublicRecordChildLinkDto } from '@/shared/lib/public-record-hierarchy'

export interface RecordChildPagesProps {
  items: PublicRecordChildLinkDto[]
}

/**
 * Section child pages (#651 PR2): a section parent (a custom-permalink page)
 * lists the pages one path segment below it. Renders nothing for ordinary
 * records or leaf pages with no children.
 */
export function RecordChildPages({ items }: RecordChildPagesProps) {
  const { t } = useTranslation()

  if (items.length === 0) {
    return null
  }

  return (
    <nav className="child-pages" aria-label={t('public.record.childPages.heading')}>
      <h2 className="child-pages__heading">{t('public.record.childPages.heading')}</h2>
      <ul className="child-pages__list">
        {items.map((child) => (
          <li className="child-pages__item" key={child.path}>
            <Link viewTransition to={child.path}>
              {child.title}
            </Link>
          </li>
        ))}
      </ul>
    </nav>
  )
}
