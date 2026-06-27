<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

/**
 * The permalink-path-derived hierarchy of a public record (#651 PR2): the
 * breadcrumb trail to it and its direct child pages. parent_id is intentionally
 * not modelled — both are derived from the permalink path at read time.
 */
final readonly class PublicRecordHierarchy
{
    /**
     * @param list<PublicRecordBreadcrumb> $breadcrumbs
     * @param list<PublicRecordChildLink> $childPages
     */
    public function __construct(
        public array $breadcrumbs,
        public array $childPages,
    ) {
    }

    public static function empty(): self
    {
        return new self([], []);
    }

    /**
     * @return array{
     *     breadcrumbs: list<array{label: string, path: string|null, current: bool}>,
     *     childPages: list<array{title: string, path: string}>
     * }
     */
    public function toArray(): array
    {
        return [
            'breadcrumbs' => array_map(
                static fn (PublicRecordBreadcrumb $crumb): array => [
                    'label' => $crumb->label,
                    'path' => $crumb->path,
                    'current' => $crumb->current,
                ],
                $this->breadcrumbs,
            ),
            'childPages' => array_map(
                static fn (PublicRecordChildLink $child): array => [
                    'title' => $child->title,
                    'path' => $child->path,
                ],
                $this->childPages,
            ),
        ];
    }
}
