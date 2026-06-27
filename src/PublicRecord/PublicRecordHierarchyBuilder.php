<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\TextField\TextFieldRepositoryInterface;

/**
 * Derives a record's breadcrumb trail and direct child pages from its permalink
 * path (#651 PR2). There is no parent_id column: a record at `/a/b/c` has
 * ancestors `/a` and `/a/b` (linked only when a published page exists there) and
 * children at `/a/b/c/{segment}`. Only custom-permalink (path-style) records get
 * a hierarchy; ordinary `/{type}/{slug}` records return an empty one.
 */
final readonly class PublicRecordHierarchyBuilder
{
    private const CHILD_LIMIT = 100;

    public function __construct(
        private EntityRepositoryInterface $entities,
        private TextFieldRepositoryInterface $textFields,
    ) {
    }

    public function build(?string $permalink, string $canonicalPath, string $currentTitle): PublicRecordHierarchy
    {
        if ($permalink === null || trim($permalink) === '') {
            return PublicRecordHierarchy::empty();
        }

        return new PublicRecordHierarchy(
            $this->buildBreadcrumbs($canonicalPath, $currentTitle),
            $this->buildChildPages($permalink),
        );
    }

    /** Resolve a record by id and build its hierarchy (client-side navigation endpoint). */
    public function buildById(int $entityId): PublicRecordHierarchy
    {
        $entity = $this->entities->findById($entityId);

        if (
            $entity === null
            || $entity->id === null
            || $entity->isDeleted
            || $entity->status !== EntityStatus::Published
            || $entity->permalink === null
        ) {
            return PublicRecordHierarchy::empty();
        }

        $title = $this->titlesByIds([$entity->id])[$entity->id]
            ?? $this->humanize(basename($entity->permalink));

        // A custom-permalink record's canonical path is the permalink itself (#651 PR1).
        return $this->build($entity->permalink, $entity->permalink, $title);
    }

    /** @return list<PublicRecordBreadcrumb> */
    private function buildBreadcrumbs(string $canonicalPath, string $currentTitle): array
    {
        $segments = [];
        foreach (explode('/', trim($canonicalPath, '/')) as $segment) {
            if ($segment !== '') {
                $segments[] = $segment;
            }
        }

        if ($segments === []) {
            return [];
        }

        $lastIndex = count($segments) - 1;

        // Resolve ancestors (every segment but the last) so crumbs use the real
        // page title and only link to segments that are actually published pages.
        $ancestorIdByPath = [];
        $ancestorIds = [];
        $cumulative = '';
        foreach ($segments as $index => $segment) {
            $cumulative .= '/' . $segment;
            if ($index === $lastIndex) {
                break;
            }
            $ancestor = $this->entities->findByPermalink($cumulative);
            if (
                $ancestor !== null
                && $ancestor->id !== null
                && !$ancestor->isDeleted
                && $ancestor->status === EntityStatus::Published
            ) {
                $ancestorIdByPath[$cumulative] = $ancestor->id;
                $ancestorIds[] = $ancestor->id;
            }
        }

        $titlesById = $this->titlesByIds($ancestorIds);

        $crumbs = [];
        $cumulative = '';
        foreach ($segments as $index => $segment) {
            $cumulative .= '/' . $segment;

            if ($index === $lastIndex) {
                $crumbs[] = new PublicRecordBreadcrumb($currentTitle, $cumulative, true);
                continue;
            }

            if (isset($ancestorIdByPath[$cumulative])) {
                $ancestorId = $ancestorIdByPath[$cumulative];
                $crumbs[] = new PublicRecordBreadcrumb(
                    $titlesById[$ancestorId] ?? $this->humanize($segment),
                    $cumulative,
                    false,
                );
            } else {
                $crumbs[] = new PublicRecordBreadcrumb($this->humanize($segment), null, false);
            }
        }

        return $crumbs;
    }

    /** @return list<PublicRecordChildLink> */
    private function buildChildPages(string $permalink): array
    {
        $children = $this->entities->findDirectChildrenByPermalink($permalink, self::CHILD_LIMIT);

        $ids = [];
        foreach ($children as $child) {
            if ($child->id !== null) {
                $ids[] = $child->id;
            }
        }

        $titlesById = $this->titlesByIds($ids);

        $links = [];
        foreach ($children as $child) {
            if ($child->id === null || $child->permalink === null) {
                continue;
            }
            $links[] = new PublicRecordChildLink(
                $titlesById[$child->id] ?? $this->humanize(basename($child->permalink)),
                $child->permalink,
            );
        }

        return $links;
    }

    /**
     * @param list<int> $entityIds
     * @return array<int, string> entityId => display title
     */
    private function titlesByIds(array $entityIds): array
    {
        if ($entityIds === []) {
            return [];
        }

        $rows = $this->textFields->findByEntityIds($entityIds);
        $byId = [];

        foreach ($rows as $row) {
            if ($row->fieldKey === 'title' && trim($row->value) !== '' && !isset($byId[$row->entityId])) {
                $byId[$row->entityId] = $row->value;
            }
        }

        foreach ($rows as $row) {
            if (!isset($byId[$row->entityId]) && trim($row->value) !== '') {
                $byId[$row->entityId] = $row->value;
            }
        }

        return $byId;
    }

    /** "about-us" → "About Us"; falls back to the raw segment for non-kebab input. */
    private function humanize(string $segment): string
    {
        $words = [];
        foreach (explode('-', $segment) as $part) {
            if ($part === '') {
                continue;
            }
            $words[] = mb_strtoupper(mb_substr($part, 0, 1)) . mb_substr($part, 1);
        }

        return $words === [] ? $segment : implode(' ', $words);
    }
}
