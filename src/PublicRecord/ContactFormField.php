<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

/**
 * One field of a contact form, as described by the issuing product's public schema.
 *
 * Deliberately a narrow subset of what contact may return: records renders inputs, it does
 * not re-implement contact's form model. Anything records cannot render is ignored rather
 * than guessed at.
 */
final readonly class ContactFormField
{
    /**
     * @param list<string> $options values for `select`; empty for every other type
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $type,
        public bool $required,
        public array $options = [],
    ) {
    }
}
