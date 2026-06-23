import { describe, expect, it } from 'vitest'
import { AppError, parseProblemDetails } from './errors'

function jsonResponse(body: unknown, status: number): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { 'Content-Type': 'application/json' },
  })
}

describe('parseProblemDetails', () => {
  it('maps a valid Problem Details document', async () => {
    const error = await parseProblemDetails(
      jsonResponse(
        {
          type: 'https://nene-records.dev/problems/validation',
          title: 'Validation failed',
          status: 422,
          instance: '/api/v1/entities',
        },
        422,
      ),
    )
    expect(error).toBeInstanceOf(AppError)
    expect(error.status).toBe(422)
    expect(error.title).toBe('Validation failed')
    expect(error.isRetryable).toBe(false)
  })

  it('falls back to the HTTP status for a valid but non-Problem-Details body', async () => {
    // A 503 with a non-conforming JSON body must NOT become status=undefined,
    // which would make isRetryable() false and skip the retry.
    const error = await parseProblemDetails(jsonResponse({ message: 'boom' }, 503))
    expect(error.status).toBe(503)
    expect(error.isRetryable).toBe(true)
  })

  it('falls back for a non-JSON body', async () => {
    const error = await parseProblemDetails(new Response('upstream down', { status: 502 }))
    expect(error.status).toBe(502)
    expect(error.isRetryable).toBe(true)
  })
})
