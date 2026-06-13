import { describe, expect, it } from 'vitest'
import {
  buildMonthMatrix,
  currentMonth,
  daysInMonth,
  formatDateKey,
  shiftMonth,
} from './month-grid'

describe('month-grid', () => {
  it('formatDateKey zero-pads', () => {
    expect(formatDateKey(2026, 6, 3)).toBe('2026-06-03')
  })

  it('daysInMonth handles leap February', () => {
    expect(daysInMonth(2024, 2)).toBe(29)
    expect(daysInMonth(2026, 2)).toBe(28)
    expect(daysInMonth(2026, 6)).toBe(30)
  })

  it('shiftMonth rolls over year boundaries', () => {
    expect(shiftMonth({ year: 2026, month: 1 }, -1)).toEqual({ year: 2025, month: 12 })
    expect(shiftMonth({ year: 2026, month: 12 }, 1)).toEqual({ year: 2027, month: 1 })
    expect(shiftMonth({ year: 2026, month: 6 }, 3)).toEqual({ year: 2026, month: 9 })
  })

  it('currentMonth reads a date', () => {
    expect(currentMonth(new Date('2026-06-13T00:00:00'))).toEqual({ year: 2026, month: 6 })
  })

  it('buildMonthMatrix pads to full weeks and contains every day once', () => {
    // June 2026: 1st is a Monday → one leading pad cell.
    const weeks = buildMonthMatrix(2026, 6)
    expect(weeks.every((week) => week.length === 7)).toBe(true)
    expect(weeks[0]?.[0]).toBe(0)
    expect(weeks[0]?.[1]).toBe(1)

    const days = weeks.flat().filter((d) => d !== 0)
    expect(days).toEqual(Array.from({ length: 30 }, (_, i) => i + 1))
  })
})
