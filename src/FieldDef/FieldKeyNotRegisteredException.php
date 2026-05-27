<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

use RuntimeException;

final class FieldKeyNotRegisteredException extends RuntimeException
{
    public function __construct(
        public readonly string $fieldKey,
        public readonly ?int $entityTypeId = null,
    ) {
        $context = $entityTypeId !== null
            ? " for entity type {$entityTypeId}"
            : '';

        parent::__construct("Field key \"{$fieldKey}\" is not registered{$context}.");
    }
}
