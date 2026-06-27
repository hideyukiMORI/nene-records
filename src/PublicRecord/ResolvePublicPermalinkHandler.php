<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use Nene2\Http\JsonResponseFactory;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\EntityType\EntityTypeRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Resolve an arbitrary custom-permalink path to `{entityTypeSlug, entityId}` for
 * the public SPA (#656). The client router is type-based (`/:entityTypeSlug/*`)
 * and cannot resolve a path like `/company/about` on its own; this lets it render
 * the right record on direct load AND on client-side navigation (breadcrumb /
 * child links). Always 200; `found:false` when no published record owns the path.
 */
final readonly class ResolvePublicPermalinkHandler
{
    private const CACHE_CONTROL = 'public, max-age=60, stale-while-revalidate=300';

    public function __construct(
        private EntityRepositoryInterface $entities,
        private EntityTypeRepositoryInterface $entityTypes,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $rawPath = $request->getQueryParams()['path'] ?? '';
        $path = is_string($rawPath) ? trim($rawPath) : '';

        $entity = ($path === '' || $path === '/') ? null : $this->entities->findByPermalink($path);

        if (
            $entity === null
            || $entity->id === null
            || $entity->isDeleted
            || $entity->status !== EntityStatus::Published
        ) {
            return $this->response->create(['found' => false], 200, ['Cache-Control' => self::CACHE_CONTROL]);
        }

        $entityType = $this->entityTypes->findById($entity->entityTypeId);

        if ($entityType === null) {
            return $this->response->create(['found' => false], 200, ['Cache-Control' => self::CACHE_CONTROL]);
        }

        return $this->response->create([
            'found' => true,
            'entityTypeSlug' => $entityType->slug,
            'entityId' => $entity->id,
        ], 200, ['Cache-Control' => self::CACHE_CONTROL]);
    }
}
