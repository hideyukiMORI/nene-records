<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use Nene2\Validation\ValidationError;

/**
 * Shared request-body parsing for the optional custom permalink field (#651),
 * used identically by the create and update entity handlers: an absent/empty
 * value means "no custom permalink", otherwise the value is normalized and
 * validated, appending a `permalink` {@see ValidationError} on failure.
 */
trait ParsesPermalinkField
{
    /**
     * @param list<ValidationError> $errors collected validation errors (appended to)
     * @return string|null the normalized permalink, or null when absent/invalid
     */
    private function parsePermalinkField(mixed $rawPermalink, array &$errors): ?string
    {
        if (!is_string($rawPermalink) || trim($rawPermalink) === '') {
            return null;
        }

        $normalized = EntityPermalink::normalize($rawPermalink);
        $error = EntityPermalink::validate($normalized);

        if ($error !== null) {
            $errors[] = new ValidationError('permalink', EntityPermalink::messageForError($error), $error);

            return null;
        }

        return $normalized;
    }
}
