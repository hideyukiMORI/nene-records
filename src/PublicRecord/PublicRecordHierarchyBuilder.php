<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use NeNeRecords\Entity\Entity;
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

        $title = $this->titlesByIds([$entity])[$entity->id]
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
        $ancestors = [];
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
                $ancestors[] = $ancestor;
            }
        }

        $titlesById = $this->titlesByIds($ancestors);

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

        $titlesById = $this->titlesByIds($children);

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
     * Entities (not ids) because the label needs `meta_title`, which the callers
     * already hold — re-fetching them here would be a second query for nothing.
     *
     * @param list<Entity> $entities
     * @return array<int, string> entityId => display label
     */
    private function titlesByIds(array $entities): array
    {
        $ids = [];
        $metaTitleById = [];
        foreach ($entities as $entity) {
            if ($entity->id === null) {
                continue;
            }
            $ids[] = $entity->id;
            $metaTitleById[$entity->id] = $entity->metaTitle;
        }

        if ($ids === []) {
            return [];
        }

        $rows = $this->textFields->findByEntityIds($ids);
        $byId = [];

        foreach ($ids as $id) {
            // Empty fallback: callers already humanize the permalink segment, which
            // is a better last resort here than a bare id.
            $label = RecordDisplayLabel::resolve($id, $rows, $metaTitleById[$id] ?? null, '');
            if ($label !== '') {
                $byId[$id] = $label;
            }
        }

        return $byId;
    }

    /** "about-us" → "About Us"; falls back to the raw segment for non-kebab input. */
    private function humanize(string $segment): string
    {
        return PermalinkLabel::humanize($segment);
    }
}
