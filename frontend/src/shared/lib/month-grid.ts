export interface MonthRef {
  /** Full year, e.g. 2026. */
  year: number
  /** 1-based month, 1 = January … 12 = December. */
  month: number
}

/** Zero-pads a number to two digits. */
export function pad2(value: number): string {
  return String(value).padStart(2, '0')
}

/** `YYYY-MM-DD` for a year/month/day. */
export function formatDateKey(year: number, month: number, day: number): string {
  return `${String(year)}-${pad2(month)}-${pad2(day)}`
}

/** Number of days in a 1-based month. */
export function daysInMonth(year: number, month: number): number {
  return new Date(year, month, 0).getDate()
}

/** The month before/after, rolling the year over at the boundaries. */
export function shiftMonth(ref: MonthRef, delta: number): MonthRef {
  const zeroBased = ref.month - 1 + delta
  const year = ref.year + Math.floor(zeroBased / 12)
  const month = ((zeroBased % 12) + 12) % 12
  return { year, month: month + 1 }
}

/** The current month from a date (defaults to now). */
export function currentMonth(now: Date = new Date()): MonthRef {
  return { year: now.getFullYear(), month: now.getMonth() + 1 }
}

/**
 * Builds a Sunday-first calendar matrix: rows of 7 day numbers, with 0 for
 * leading/trailing padding cells outside the month.
 */
export function buildMonthMatrix(year: number, month: number): number[][] {
  const firstWeekday = new Date(year, month - 1, 1).getDay() // 0 = Sunday
  const total = daysInMonth(year, month)

  const cells: number[] = Array.from({ length: firstWeekday }, () => 0)
  for (let day = 1; day <= total; day++) {
    cells.push(day)
  }
  while (cells.length % 7 !== 0) {
    cells.push(0)
  }

  const weeks: number[][] = []
  for (let i = 0; i < cells.length; i += 7) {
    weeks.push(cells.slice(i, i + 7))
  }
  return weeks
}
