import { authStore } from '@/entities/auth/model'
import { env } from '@/shared/config/env'
import { AppError, parseProblemDetails } from '@/shared/api/errors'

type HttpMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'

// Custom header required by the API for cookie-authenticated mutations (CSRF
// defense): a cross-site form post cannot set it without a CORS preflight.
const CSRF_HEADER: Readonly<Record<string, string>> = { 'X-Requested-With': 'fetch' }

interface RequestOptions {
  method?: HttpMethod
  body?: unknown
  signal?: AbortSignal
}

/**
 * Reacts to auth-related error responses with a side-effecting redirect.
 * Shared by request() and upload() so the 401/403 handling stays in one place.
 */
function handleErrorResponse(response: Response, path: string): void {
  if (response.status === 401 && !path.includes('/auth/login')) {
    authStore.clearSession()
    window.location.href = '/login'
  }
  if (response.status === 403) {
    window.location.href = '/forbidden'
  }
}

async function request<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const base = env.apiBaseUrl.replace(/\/$/, '')
  const url = `${base}${path}`
  const headers: Record<string, string> = { ...CSRF_HEADER }
  if (options.body !== undefined) {
    headers['Content-Type'] = 'application/json'
  }
  // Auth travels in the HttpOnly session cookie (sent via credentials:'include').

  const response = await fetch(url, {
    method: options.method ?? 'GET',
    headers,
    body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
    credentials: 'include',
    signal: options.signal,
  })

  if (!response.ok) {
    handleErrorResponse(response, path)
    throw await parseProblemDetails(response)
  }

  if (response.status === 204) {
    return undefined as T
  }

  return (await response.json()) as T
}

export const apiClient = {
  get<T>(path: string, signal?: AbortSignal): Promise<T> {
    return request<T>(path, signal !== undefined ? { signal } : {})
  },
  post<T>(path: string, body: unknown): Promise<T> {
    return request<T>(path, { method: 'POST', body })
  },
  put<T>(path: string, body: unknown): Promise<T> {
    return request<T>(path, { method: 'PUT', body })
  },
  patch<T>(path: string, body: unknown): Promise<T> {
    return request<T>(path, { method: 'PATCH', body })
  },
  delete(path: string): Promise<undefined> {
    return request<undefined>(path, { method: 'DELETE' })
  },
  async upload<T>(path: string, formData: FormData): Promise<T> {
    const base = env.apiBaseUrl.replace(/\/$/, '')
    const url = `${base}${path}`

    const response = await fetch(url, {
      method: 'POST',
      headers: { ...CSRF_HEADER },
      body: formData,
      credentials: 'include',
    })

    if (!response.ok) {
      handleErrorResponse(response, path)
      throw await parseProblemDetails(response)
    }

    return (await response.json()) as T
  },
}

export { AppError }
