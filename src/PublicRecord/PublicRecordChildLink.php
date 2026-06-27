<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

/**
 * A direct child page of a section parent, derived from the permalink path
 * (the parent at `/a/b` lists records whose permalink is `/a/b/{segment}`). #651 PR2.
 */
final readonly class PublicRecordChildLink
{
    public function __construct(
        public string $title,
        public string $path,
    ) {
    }
}
