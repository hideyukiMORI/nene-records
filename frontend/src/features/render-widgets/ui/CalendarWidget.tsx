import { useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { useEntitiesByDateRange } from '@/entities/entity'
import { useTranslation } from '@/shared/i18n'
import {
  buildMonthMatrix,
  currentMonth,
  daysInMonth,
  formatDateKey,
  shiftMonth,
} from '@/shared/lib/month-grid'
import { Button, Stack, Text } from '@/shared/ui'

const WEEKDAY_KEYS = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'] as const

/** Month calendar; days with published records link to their date archive. */
export function CalendarWidget() {
  const { t } = useTranslation()
  const [ref, setRef] = useState(() => currentMonth())

  const from = formatDateKey(ref.year, ref.month, 1)
  const to = formatDateKey(ref.year, ref.month, daysInMonth(ref.year, ref.month))
  const { data } = useEntitiesByDateRange(from, to)

  const daysWithPosts = useMemo(() => {
    const set = new Set<number>()
    for (const entity of data?.items ?? []) {
      if (entity.publishedAt === null) {
        continue
      }
      const day = Number(entity.publishedAt.slice(8, 10))
      if (!Number.isNaN(day)) {
        set.add(day)
      }
    }
    return set
  }, [data?.items])

  const weeks = useMemo(() => buildMonthMatrix(ref.year, ref.month), [ref])

  return (
    <Stack gap="sm">
      <div className="flex items-center justify-between gap-inline-sm">
        <Button
          variant="secondary"
          size="sm"
          aria-label={t('widgets.calendar.prevMonth')}
          onClick={() => {
            setRef((current) => shiftMonth(current, -1))
          }}
        >
          ‹
        </Button>
        <Text as="span" variant="heading-sm">
          {t('widgets.calendar.monthLabel', {
            year: String(ref.year),
            month: String(ref.month),
          })}
        </Text>
        <Button
          variant="secondary"
          size="sm"
          aria-label={t('widgets.calendar.nextMonth')}
          onClick={() => {
            setRef((current) => shiftMonth(current, 1))
          }}
        >
          ›
        </Button>
      </div>

      <table className="w-full table-fixed text-center text-caption">
        <thead>
          <tr>
            {WEEKDAY_KEYS.map((key) => (
              <th key={key} scope="col" className="py-stack-xs font-normal text-text-muted">
                {t(`widgets.calendar.weekday.${key}`)}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {weeks.map((week, weekIndex) => (
            <tr key={weekIndex}>
              {week.map((day, dayIndex) => (
                <td key={dayIndex} className="py-stack-xs">
                  {day === 0 ? (
                    <span aria-hidden="true" />
                  ) : daysWithPosts.has(day) ? (
                    <Link
                      to={`/archive/${String(ref.year)}/${String(ref.month)}/${String(day)}`}
                      className="text-accent underline hover:no-underline"
                    >
                      {day}
                    </Link>
                  ) : (
                    <span className="text-text-muted">{day}</span>
                  )}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </Stack>
  )
}
