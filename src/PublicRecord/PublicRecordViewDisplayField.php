<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

final readonly class PublicRecordViewDisplayField
{
    /**
     * @param list<array{label: string, href: string}> $relationLinks
     * @param string|null                              $blocksDocument the raw blocks document, for
     *                                                                 `blocks` fields only. Carried
     *                                                                 beside `displayValue` rather
     *                                                                 than inside it so the JSON API
     *                                                                 keeps returning exactly what it
     *                                                                 returned before (#1030); only the
     *                                                                 SSR template reads this.
     */
    public function __construct(
        public string $fieldKey,
        public string $dataType,
        public string $displayValue,
        public array $relationLinks = [],
        public ?string $blocksDocument = null,
    ) {
    }
}
