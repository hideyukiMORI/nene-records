export interface ValidationError {
  field: string
  message: string
  code: string
}

export interface ProblemDetails {
  type: string
  title: string
  status: number
  detail?: string
  instance: string
  errors?: readonly ValidationError[]
}

export class AppError extends Error {
  readonly status: number
  readonly type: string
  readonly title: string
  readonly detail?: string
  readonly instance: string
  readonly errors?: readonly ValidationError[]

  constructor(problem: ProblemDetails) {
    super(problem.title)
    this.name = 'AppError'
    this.status = problem.status
    this.type = problem.type
    this.title = problem.title
    this.instance = problem.instance
    if (problem.detail !== undefined) {
      this.detail = problem.detail
    }
    if (problem.errors !== undefined) {
      this.errors = problem.errors
    }
  }

  get isRetryable(): boolean {
    return this.status >= 500 || this.status === 429
  }
}

function isProblemDetails(value: unknown): value is ProblemDetails {
  return (
    typeof value === 'object' &&
    value !== null &&
    typeof (value as ProblemDetails).status === 'number' &&
    typeof (value as ProblemDetails).title === 'string'
  )
}

export async function parseProblemDetails(response: Response): Promise<AppError> {
  try {
    const body: unknown = await response.json()
    // Only trust a body that is actually a Problem Details document. A valid but
    // non-conforming JSON (e.g. {}, [], {message}) would otherwise yield
    // status=undefined, breaking AppError.isRetryable (5xx treated as final).
    if (isProblemDetails(body)) {
      return new AppError({ ...body, instance: body.instance || response.url })
    }
  } catch {
    // Non-JSON body — fall through to the synthetic error below.
  }
  return new AppError({
    type: 'about:blank',
    title: response.statusText || 'Request failed',
    status: response.status,
    instance: response.url,
  })
}
