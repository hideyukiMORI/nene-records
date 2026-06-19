import type { CSSProperties } from 'react'
import type { PublicThemeMeta } from '@/shared/lib/public-themes'
import { inkOn } from '@/shared/lib/perceived-lightness'

const PILL: CSSProperties = { borderRadius: 9999, display: 'block' }

/**
 * A tiny live mockup of a theme rendered straight from its swatch tokens
 * (surface · raised · accent) — far more recognisable than three flat stripes,
 * and generated from the theme itself so every runtime theme gets a thumbnail
 * with no image upload (#450). Ink/border colours are derived from the swatch
 * luminance, so it works for built-in and runtime themes alike. Dimensions are
 * inline styles (arbitrary Tailwind values are disallowed in features/).
 */
export function ThemeMiniPreview({ preview }: { preview: PublicThemeMeta['preview'] }) {
  const { surface, raised, accent } = preview
  const ink = inkOn(surface)
  const cardInk = inkOn(raised)
  const onAccent = inkOn(accent)

  return (
    <span
      aria-hidden
      style={{ aspectRatio: '16 / 7', background: surface, color: ink, gap: '4%', padding: '5%' }}
      className="flex w-full flex-col overflow-hidden rounded-sm border border-border"
    >
      {/* Header row: brand mark + nav lines */}
      <span className="flex items-center" style={{ gap: '3%' }}>
        <span style={{ ...PILL, background: accent, width: 10, height: 10, flexShrink: 0 }} />
        <span style={{ ...PILL, background: ink, opacity: 0.4, width: '24%', height: 5 }} />
        <span
          style={{
            ...PILL,
            background: ink,
            opacity: 0.22,
            width: '16%',
            height: 5,
            marginLeft: 'auto',
          }}
        />
      </span>

      {/* Body card: title, copy line, accent button */}
      <span
        className="flex flex-1 flex-col"
        style={{ background: raised, gap: 7, padding: '6%', borderRadius: 3 }}
      >
        <span style={{ ...PILL, background: cardInk, opacity: 0.9, width: '58%', height: 7 }} />
        <span style={{ ...PILL, background: cardInk, opacity: 0.4, width: '88%', height: 5 }} />
        <span
          className="flex items-center justify-center"
          style={{
            background: accent,
            width: '36%',
            height: 13,
            borderRadius: 9999,
            marginTop: 'auto',
          }}
        >
          <span style={{ ...PILL, background: onAccent, opacity: 0.85, width: '60%', height: 4 }} />
        </span>
      </span>
    </span>
  )
}
