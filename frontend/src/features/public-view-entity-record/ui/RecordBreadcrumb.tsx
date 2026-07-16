import { Link } from 'react-router-dom'
import { useTranslation } from '@/shared/i18n'
import type { PublicRecordBreadcrumbDto } from '@/shared/lib/public-record-hierarchy'

export interface RecordBreadcrumbProps {
  items: PublicRecordBreadcrumbDto[]
}

/**
 * Permalink-path breadcrumb (#651 PR2): a Home crumb plus one crumb per path
 * segment. Segments backed by a published page link to it; structural segments
 * and the current page render as plain text. The crawlable BreadcrumbList
 * JSON-LD is emitted server-side — this is the visible, localized trail.
 */
export function RecordBreadcrumb({ items }: RecordBreadcrumbProps) {
  const { t } = useTranslation()

  if (items.length === 0) {
    return null
  }

  return (
    <nav className="breadcrumb" aria-label={t('public.record.breadcrumb.label')}>
      <ol className="breadcrumb__list">
        <li className="breadcrumb__item">
          <Link viewTransition to="/">
            {t('public.record.breadcrumb.home')}
          </Link>
        </li>
        {items.map((crumb, index) => (
          <li className="breadcrumb__item" key={`${crumb.path ?? crumb.label}-${String(index)}`}>
            {crumb.current ? (
              <span aria-current="page">{crumb.label}</span>
            ) : crumb.path === null ? (
              <span>{crumb.label}</span>
            ) : (
              <Link viewTransition to={crumb.path}>
                {crumb.label}
              </Link>
            )}
          </li>
        ))}
      </ol>
    </nav>
  )
}
