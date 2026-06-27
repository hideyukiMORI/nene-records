<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Public JSON endpoint returning a record's permalink-derived breadcrumb trail
 * and child pages (#651 PR2). The SSR shell renders this from the bootstrap on
 * first paint; the SPA refetches here so client-side navigation stays correct.
 * Returns an empty hierarchy (never 404) for missing / unpublished / flat records.
 */
final readonly class GetPublicRecordHierarchyHandler
{
    private const CACHE_CONTROL = 'public, max-age=60, stale-while-revalidate=300';

    public function __construct(
        private PublicRecordHierarchyBuilder $builder,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($parameters['id'] ?? 0);

        $hierarchy = $id > 0 ? $this->builder->buildById($id) : PublicRecordHierarchy::empty();

        return $this->response->create($hierarchy->toArray(), 200, [
            'Cache-Control' => self::CACHE_CONTROL,
        ]);
    }
}
