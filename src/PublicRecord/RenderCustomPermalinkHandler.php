<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use NeNeRecords\Entity\EntityPermalink;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\EntityType\EntityTypeRepositoryInterface;
use NeNeRecords\Http\PublicPermalinkRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Input resolution for per-record custom permalinks (#651): the single place that
 * maps an incoming request path → the record that stored that exact permalink →
 * its crawlable SSR view.
 *
 * Reused by both call sites so there is one mapping rule:
 *  - {@see \NeNeRecords\PublicRecord\RenderPublicPermalinkHandler} consults it
 *    FIRST, so a custom permalink wins over a colliding `/{type}/{slug|id}` route;
 *  - {@see \NeNeRecords\Http\CustomPermalinkResolver} consults it as a 404 edge
 *    layer, so arbitrary-depth permalinks (e.g. `/company/about/team`) that match
 *    no fixed-arity catch-all route still resolve.
 *
 * Returns null (never throws for a normal miss) when nothing publicly-visible
 * claims the path, so callers fall through to the unchanged existing behaviour.
 */
final readonly class RenderCustomPermalinkHandler implements PublicPermalinkRendererInterface
{
    public function __construct(
        private EntityRepositoryInterface $entities,
        private EntityTypeRepositoryInterface $entityTypes,
        private RenderPublicRecordViewHandler $viewRenderer,
    ) {
    }

    public function renderByPermalink(string $path, ServerRequestInterface $request): ?ResponseInterface
    {
        $normalized = EntityPermalink::normalize($path);

        if ($normalized === '') {
            return null;
        }

        $entity = $this->entities->findByPermalink($normalized);

        if ($entity === null || $entity->id === null) {
            return null;
        }

        $entityType = $this->entityTypes->findById($entity->entityTypeId);

        if ($entityType === null) {
            return null;
        }

        try {
            // Resolve by id (most precise). The view use case enforces visibility
            // (published, not deleted, type matches), so a draft/deleted record at
            // this permalink 404s here and the caller falls through.
            return $this->viewRenderer->renderEntity($entityType->slug, null, $entity->id, $request);
        } catch (PublicRecordNotFoundException | PublicEntityTypeNotFoundException) {
            return null;
        }
    }
}
