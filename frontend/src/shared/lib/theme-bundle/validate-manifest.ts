import Ajv2020, { type ErrorObject } from 'ajv/dist/2020'

export interface ValidationIssue {
  /** Where the problem is (JSON pointer for manifest, selector/decl for CSS). */
  path: string
  message: string
}

/**
 * Validate a theme manifest object against the public-theme JSON Schema
 * (docs/theming/public-theme.schema.json). Pure: callers load both inputs.
 * A fresh Ajv instance per call keeps it stateless (no `$id` cache clashes).
 */
export function validateManifest(
  manifest: unknown,
  schema: Record<string, unknown>,
): ValidationIssue[] {
  const ajv = new Ajv2020({ allErrors: true, strict: false })
  const validate = ajv.compile(schema)

  if (validate(manifest)) {
    return []
  }

  return (validate.errors ?? []).map((error: ErrorObject) => {
    const extra =
      'additionalProperty' in error.params ? `: ${String(error.params.additionalProperty)}` : ''
    return {
      path: error.instancePath === '' ? '(root)' : error.instancePath,
      message: `${error.message ?? 'invalid'}${extra}`,
    }
  })
}
