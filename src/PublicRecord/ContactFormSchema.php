<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

final readonly class ContactFormSchema
{
    /**
     * Wording that belongs to the issuing product, not to records: the consent sentence is
     * that product's legal text, and the submit label is its copy. records only falls back to
     * English when the schema omits them — see the SSR i18n gap tracked in #1034.
     *
     * @param list<ContactFormField> $fields
     */
    public function __construct(
        public string $formKey,
        public array $fields,
        public bool $consentRequired = false,
        public ?string $submitLabel = null,
        public ?string $consentLabel = null,
    ) {
    }
}
