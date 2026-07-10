import { useLayoutEffect, useRef, useState } from 'react'
import type { KnobOption } from '@/shared/lib/theme-customization'

/**
 * Segmented control (#787): the redesign replaces small-enum selects with a
 * segment row whose raised thumb slides between choices, so the current value
 * and the alternatives are always visible. `wrap` drops the sliding thumb and
 * lets long option sets wrap onto multiple lines (per-button raised state).
 *
 * An empty-string option value means "theme default" (knob unset) — callers
 * prepend it via `defaultLabel` so every knob stays clearable.
 */
export function Segmented({
  ariaLabel,
  value,
  options,
  defaultLabel,
  disabled = false,
  wrap = false,
  onChange,
}: {
  ariaLabel: string
  /** Current value; undefined = theme default (the '' segment). */
  value: string | undefined
  options: readonly KnobOption[]
  /** When set, a leading "theme default" segment (value '') is rendered. */
  defaultLabel?: string | undefined
  disabled?: boolean
  wrap?: boolean
  onChange: (value: string | undefined) => void
}) {
  const allOptions: readonly KnobOption[] =
    defaultLabel !== undefined ? [{ value: '', label: defaultLabel }, ...options] : options
  const current = value ?? ''

  const rootRef = useRef<HTMLDivElement>(null)
  const [thumb, setThumb] = useState<{
    left: number
    top: number
    width: number
    height: number
  } | null>(null)

  // Position the sliding thumb under the active segment (top/height too, so a
  // wrapped multi-row segment still highlights the right row). Re-measured on
  // value change and container resize (font swaps, locale changes, breakpoints).
  useLayoutEffect(() => {
    if (wrap) return
    const root = rootRef.current
    if (root === null) return
    const measure = () => {
      const active = root.querySelector<HTMLButtonElement>("button[aria-pressed='true']")
      if (active === null) {
        setThumb(null)
        return
      }
      setThumb({
        left: active.offsetLeft,
        top: active.offsetTop,
        width: active.offsetWidth,
        height: active.offsetHeight,
      })
    }
    measure()
    const observer = new ResizeObserver(measure)
    observer.observe(root)
    return () => {
      observer.disconnect()
    }
  }, [wrap, current, allOptions.length])

  return (
    <div
      ref={rootRef}
      className={wrap ? 'ws-seg wrap' : 'ws-seg'}
      role="group"
      aria-label={ariaLabel}
    >
      {allOptions.map((option) => (
        <button
          key={option.value}
          type="button"
          aria-pressed={option.value === current}
          disabled={disabled}
          onClick={() => {
            onChange(option.value === '' ? undefined : option.value)
          }}
        >
          {option.label}
        </button>
      ))}
      {!wrap && thumb !== null ? (
        <span
          className="ws-seg-thumb"
          aria-hidden="true"
          style={{ left: thumb.left, top: thumb.top, width: thumb.width, height: thumb.height }}
        />
      ) : null}
    </div>
  )
}
