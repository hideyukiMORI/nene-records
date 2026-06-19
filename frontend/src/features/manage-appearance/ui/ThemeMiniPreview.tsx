import type { CSSProperties } from 'react'
import type { PublicThemeMeta } from '@/shared/lib/public-themes'
import { inkOn } from '@/shared/lib/perceived-lightness'

const PILL: CSSProperties = { borderRadius: 9999, display: 'block' }

/**
 * A tiny live mockup of a theme rendered straight from its swatch tokens
 * (surface · raised · accent) — generated from the theme itself so every runtime
 * theme gets a recognisable thumbnail with no image upload (#450). Concretised
 * into a mini "article site": header, hero/eyecatch, and a two-card grid (#452).
 * Ink/border colours are derived from the swatch luminance, so it works for
 * built-in and runtime themes alike. Dimensions are inline styles (arbitrary
 * Tailwind values are disallowed in features/).
 */
export function ThemeMiniPreview({ preview }: { preview: PublicThemeMeta['preview'] }) {
  const { surface, raised, accent } = preview
  const ink = inkOn(surface)
  const cardInk = inkOn(raised)

  // A neutral "image" placeholder reads as media without inventing a colour.
  const media = cardInk === '#16181d' ? 'rgba(0, 0, 0, 0.12)' : 'rgba(255, 255, 255, 0.14)'

  return (
    <span
      aria-hidden
      style={{ aspectRatio: '16 / 7', background: surface, color: ink, gap: '5%', padding: '5%' }}
      className="flex w-full flex-col overflow-hidden rounded-sm border border-border"
    >
      {/* Header: brand mark + nav lines */}
      <span className="flex items-center" style={{ gap: '3%' }}>
        <span style={{ ...PILL, background: accent, width: 9, height: 9, flexShrink: 0 }} />
        <span style={{ ...PILL, background: ink, opacity: 0.4, width: '22%', height: 4 }} />
        <span
          style={{
            ...PILL,
            background: ink,
            opacity: 0.22,
            width: '14%',
            height: 4,
            marginLeft: 'auto',
          }}
        />
      </span>

      {/* Hero / eyecatch: media panel with an accent top rule + headline */}
      <span
        className="flex flex-col justify-end"
        style={{
          flex: '1.6',
          background: media,
          borderTop: `2px solid ${accent}`,
          borderRadius: 3,
          padding: '5%',
          gap: 4,
        }}
      >
        <span style={{ ...PILL, background: cardInk, opacity: 0.85, width: '62%', height: 6 }} />
        <span style={{ ...PILL, background: cardInk, opacity: 0.4, width: '40%', height: 4 }} />
      </span>

      {/* Article grid: two mini cards (image + title line) */}
      <span className="flex" style={{ flex: '1', gap: '5%' }}>
        {[0, 1].map((i) => (
          <span
            key={i}
            className="flex flex-1 flex-col"
            style={{ background: raised, borderRadius: 3, padding: '6%', gap: 3, minWidth: 0 }}
          >
            <span style={{ background: media, borderRadius: 2, flex: '1', minHeight: 8 }} />
            <span
              style={{ ...PILL, background: cardInk, opacity: 0.55, width: '80%', height: 4 }}
            />
          </span>
        ))}
      </span>
    </span>
  )
}
