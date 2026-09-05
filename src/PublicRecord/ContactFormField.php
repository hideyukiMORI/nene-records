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
     * The issuing product's own bot trap. It is the one declared type records must actively
     * refuse to render: the `default` arm of the renderer turns unknown types into a plain
     * text input so an author's field still collects, and for a honeypot that is exactly wrong
     * — a visible, empty-labelled box that people fill in and the issuing product then discards
     * without an error (#1066). records runs its own hidden honeypot instead
     * (`SubmitContactFormHandler::HONEYPOT_FIELD`).
     */
    public const TYPE_HONEYPOT = 'honeypot';

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

    public function isHoneypot(): bool
    {
        return $this->type === self::TYPE_HONEYPOT;
    }
}
