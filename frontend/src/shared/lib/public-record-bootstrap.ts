import type { PublicSettingListDto } from '@/entities/setting/api-types'
import type { PublicRecordHierarchyDto } from '@/shared/lib/public-record-hierarchy'

const BOOTSTRAP_SCRIPT_ID = 'nene-records-public-record-bootstrap'

export interface PublicRecordBootstrapFieldListDto {
  items: unknown[]
  limit: number
  offset: number
}

export interface PublicRecordBootstrapRelationQuery {
  fieldKey: string
  items: Array<{ field_key: string; target_entity_id: number }>
}

/**
 * A contact form's public schema, resolved server-side while rendering the SSR form and
 * handed to the client in the bootstrap (#1030).
 *
 * The client never fetches this itself: rendering from the same resolved data is what makes
 * the crawlable form and the hydrated form provably identical, and it costs no extra request.
 * Nothing secret is in here — the form key is a handle, and the credential lives only on the
 * submission path (#1031).
 */
export interface PublicContactFormFieldDto {
  key: string
  label: string
  type: string
  required: boolean
  options: string[]
}

export interface PublicContactFormSchemaDto {
  formKey: string
  /** Where the form posts — always the records-side proxy, never the issuing product. */
  submitPath: string
  /** Name of the hidden decoy input the proxy checks (#1031). */
  honeypotField?: string
  consentRequired: boolean
  consentLabel: string | null
  submitLabel: string | null
  fields: PublicContactFormFieldDto[]
}

export interface PublicRecordBootstrapDto {
  entityTypeSlug: string
  entityTypeId: number
  entityId: number
  entityTypes: PublicRecordBootstrapFieldListDto
  entity: {
    id: number
    entity_type_id: number
    is_deleted: boolean
    deleted_at: string | null
  }
  fieldDefs: PublicRecordBootstrapFieldListDto
  textFields: PublicRecordBootstrapFieldListDto
  intFields: PublicRecordBootstrapFieldListDto
  enumFields: PublicRecordBootstrapFieldListDto
  boolFields: PublicRecordBootstrapFieldListDto
  dateTimeFields: PublicRecordBootstrapFieldListDto
  entityRelations: PublicRecordBootstrapRelationQuery[]
  relationTextFieldsByEntityTypeId: Record<string, PublicRecordBootstrapFieldListDto>
  publicSettings?: PublicSettingListDto
  hierarchy?: PublicRecordHierarchyDto
  /** The path the SPA resolves this record by — lets us seed the resolve query (#881). */
  canonicalPath?: string
  /** Form key => schema, for the contact-form blocks on this page (#1030). */
  contactForms?: Record<string, PublicContactFormSchemaDto>
}

/**
 * The schema for one contact form, or null when the page carries none (no block, or the
 * server could not resolve it — in which case the server rendered a visible notice and there
 * is nothing for the client to draw either).
 */
export function readContactFormSchema(formKey: string): PublicContactFormSchemaDto | null {
  return readPublicRecordBootstrap()?.contactForms?.[formKey] ?? null
}

export function readPublicRecordBootstrap(): PublicRecordBootstrapDto | null {
  if (typeof document === 'undefined') {
    return null
  }

  const element = document.getElementById(BOOTSTRAP_SCRIPT_ID)

  if (element === null) {
    return null
  }

  const raw = element.textContent.trim()

  if (raw === '') {
    return null
  }

  try {
    return JSON.parse(raw) as PublicRecordBootstrapDto
  } catch {
    return null
  }
}
