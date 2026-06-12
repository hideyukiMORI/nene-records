import { mediaSrcSet } from '@/shared/lib/media-derivatives'

export interface ResponsiveImageProps {
  src: string
  alt: string
  className?: string
  /** Maps to the <img> sizes attribute; defaults to a single-column layout. */
  sizes?: string
  width?: number | null
  height?: number | null
}

const DEFAULT_SIZES = '(max-width: 768px) 100vw, 768px'

/**
 * Renders an <img> that serves resized derivatives via srcset when the source is
 * a media-library image, and falls back to the raw URL otherwise. Always lazy.
 */
export function ResponsiveImage({
  src,
  alt,
  className,
  sizes,
  width,
  height,
}: ResponsiveImageProps) {
  const responsive = mediaSrcSet(src)

  return (
    <img
      src={responsive?.src ?? src}
      srcSet={responsive?.srcSet}
      sizes={responsive !== null ? (sizes ?? DEFAULT_SIZES) : undefined}
      alt={alt}
      loading="lazy"
      decoding="async"
      width={width ?? undefined}
      height={height ?? undefined}
      className={className}
    />
  )
}
