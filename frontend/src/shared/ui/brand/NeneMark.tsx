export interface NeneMarkProps {
  /** Pixel size of the square mark. */
  size?: number
  className?: string
  'aria-hidden'?: boolean
}

/**
 * NeNe Records brand mark — the "Chevron" (double caret): forward motion,
 * speed, the developer/CLI feel. Sharp miter joins to match the display type,
 * one colour via `currentColor` (set the parent's text color to theme it).
 */
export function NeneMark({
  size = 24,
  className,
  'aria-hidden': ariaHidden = true,
}: NeneMarkProps) {
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 48 48"
      fill="none"
      stroke="currentColor"
      strokeWidth={6}
      strokeLinejoin="miter"
      strokeLinecap="butt"
      className={className}
      aria-hidden={ariaHidden}
      role="img"
    >
      <polyline points="11,11 24,24 11,37" />
      <polyline points="26,11 39,24 26,37" />
    </svg>
  )
}
