<?php

declare(strict_types=1);

namespace NeNeRecords\ContactSubmission;

/**
 * The body records sends to contact's ingest endpoint.
 *
 * Split out from the sender because this — not the cURL plumbing — is the part the cross-repo
 * contract constrains, so it is the part worth pinning in a test:
 *
 * - the form is addressed by **`public_form_key`** and never by `contact_form_id` (contact
 *   ruling 2026-07-30 option B). Sending both is a 422 upstream *by design*, so a bug that
 *   added the second key would be caught in production rather than here — hence the pin.
 * - `source` is `first_party`, contact's vocabulary. Not "records": the value outlives this
 *   caller and renaming a released `source` value is not allowed.
 */
final class ContactIngestPayload
{
    public const SOURCE = 'first_party';

    /**
     * @param non-empty-string      $formKey
     * @param array<string, string> $values
     *
     * @return array<string, mixed>
     */
    public static function build(string $formKey, array $values, bool $consent): array
    {
        return [
            'source' => self::SOURCE,
            'public_form_key' => $formKey,
            'field_values' => $values,
            'consent' => $consent,
        ];
    }
}
