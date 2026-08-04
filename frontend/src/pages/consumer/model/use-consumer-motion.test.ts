import { renderHook } from '@testing-library/react'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { useConsumerMotion } from './use-consumer-motion'

function stubReducedMotion(matches: boolean) {
  vi.stubGlobal(
    'matchMedia',
    vi.fn().mockReturnValue({
      matches,
      addEventListener: () => {},
      removeEventListener: () => {},
    }),
  )
}

afterEach(() => {
  vi.unstubAllGlobals()
})

describe('useConsumerMotion', () => {
  it('passes the reveal flag through when motion is allowed', () => {
    stubReducedMotion(false)
    const { result } = renderHook(() => useConsumerMotion({ 'data-motion-reveal': 'subtle' }))
    expect(result.current.reveal).toBe('subtle')
  })

  it('forces reveal off under reduced-motion', () => {
    stubReducedMotion(true)
    const { result } = renderHook(() => useConsumerMotion({ 'data-motion-reveal': 'standard' }))
    expect(result.current.reveal).toBe('off')
  })

  it('defaults to off when no motion flag is present', () => {
    stubReducedMotion(false)
    const { result } = renderHook(() => useConsumerMotion({}))
    expect(result.current.reveal).toBe('off')
  })

  it('passes the header flag through and defaults to static', () => {
    stubReducedMotion(false)
    expect(
      renderHook(() => useConsumerMotion({ 'data-motion-header': 'shrink' })).result.current.header,
    ).toBe('shrink')
    expect(renderHook(() => useConsumerMotion({})).result.current.header).toBe('static')
  })

  it('forces the header static under reduced-motion', () => {
    stubReducedMotion(true)
    const { result } = renderHook(() => useConsumerMotion({ 'data-motion-header': 'shrink' }))
    expect(result.current.header).toBe('static')
  })
})
