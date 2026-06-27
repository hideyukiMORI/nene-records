<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

/**
 * One crumb of a permalink-path-derived breadcrumb trail (#651 PR2).
 * `path` is the canonical path to link to, or null for a structural segment
 * that has no published page of its own (rendered as plain text). `current`
 * marks the page being viewed (last crumb, rendered without a link).
 */
final readonly class PublicRecordBreadcrumb
{
    public function __construct(
        public string $label,
        public ?string $path,
        public bool $current,
    ) {
    }
}
