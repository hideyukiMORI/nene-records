<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use Nene2\Validation\ValidationError;

/**
 * Shared request-body parsing for tri-state boolean fields (#775) —
 * `show_comments` / `show_related` on the create and update entity handlers:
 * null/omitted means "follow the site-wide `record_page_config` setting",
 * otherwise the value must be a JSON boolean, appending a {@see ValidationError}
 * for anything else.
 */
trait ParsesTriStateBoolField
{
    /**
     * @param array<string, mixed> $body parsed JSON request body
     * @param list<ValidationError> $errors collected validation errors (appended to)
     */
    private function parseTriStateBoolField(array $body, string $key, array &$errors): ?bool
    {
        $raw = $body[$key] ?? null;

        if ($raw !== null && !is_bool($raw)) {
            $errors[] = new ValidationError($key, 'Must be a boolean or null.', 'invalid');

            return null;
        }

        return $raw;
    }
}
