<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

/** An entity type's public archive page (#877). */
final readonly class GetPublicTypeArchiveOutput
{
    /** @param list<PublicTypeArchiveItem> $items */
    public function __construct(
        public string $typeSlug,
        public string $typeName,
        public array $items,
        public int $total,
        public int $offset,
        public int $pageSize,
    ) {
    }

    /** Offset of the previous page, or null on the first page. */
    public function prevOffset(): ?int
    {
        if ($this->offset <= 0) {
            return null;
        }

        return max(0, $this->offset - $this->pageSize);
    }

    /** Offset of the next page, or null when this is the last one. */
    public function nextOffset(): ?int
    {
        $next = $this->offset + $this->pageSize;

        return $next < $this->total ? $next : null;
    }
}
