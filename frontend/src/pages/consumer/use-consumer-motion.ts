import { usePrefersReducedMotion } from '@/shared/lib/motion/use-prefers-reduced-motion'

/**
 * Consumer motion orchestrator (#371). Composes the theme's declarative motion
 * flags (read from the `data-motion-*` attributes applied to `.nene-public`)
 * with the live `prefers-reduced-motion` setting into the set of *effective*
 * capabilities. Under reduced-motion every capability is forced off — this is
 * the JS layer of the 3-layer reduced-motion gate (CSS + JS + theme data).
 *
 * Themes never ship behaviour, only these enumerated flags; the first-party
 * hooks in `shared/lib/motion` implement the actual motion.
 */
export interface ConsumerMotion {
  /** `off` | `subtle` | `standard` — forced `off` under reduced-motion. */
  reveal: string
}

export function useConsumerMotion(flagAttrs: Record<string, string>): ConsumerMotion {
  const reduced = usePrefersReducedMotion()

  return {
    reveal: reduced ? 'off' : (flagAttrs['data-motion-reveal'] ?? 'off'),
  }
}
