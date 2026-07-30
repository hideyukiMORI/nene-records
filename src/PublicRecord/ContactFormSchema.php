<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

final readonly class ContactFormSchema
{
    /**
     * @param list<ContactFormField> $fields
     */
    public function __construct(
        public string $formKey,
        public array $fields,
        public bool $consentRequired = false,
        public ?string $submitLabel = null,
    ) {
    }
}
