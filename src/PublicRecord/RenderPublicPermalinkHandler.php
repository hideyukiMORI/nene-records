<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use Nene2\Routing\Router;
use NeNeRecords\EntityType\EntityTypeRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Serves crawlable server-rendered HTML at the user-facing permalink
 * (e.g. `/posts/42`, `/posts/2026/06/my-article`) — the single-origin model
 * where the PHP app fronts public content URLs and the SPA hydrates on top.
 *
 * The route patterns capture the path as `p0..pN` (max-param so any more-specific
 * route always wins the router's param-count sort). `p0` is the entity-type slug;
 * the remaining segments are reverse-resolved against the type's permalink pattern.
 */
final readonly class RenderPublicPermalinkHandler
{
    private const SEGMENT_KEYS = ['p0', 'p1', 'p2', 'p3', 'p4'];

    public function __construct(
        private EntityTypeRepositoryInterface $entityTypes,
        private RenderPublicRecordViewHandler $viewRenderer,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $parameters = is_array($parameters) ? $parameters : [];

        $segments = [];
        foreach (self::SEGMENT_KEYS as $key) {
            $value = trim((string) ($parameters[$key] ?? ''));
            if ($value !== '') {
                $segments[] = $value;
            }
        }

        $typeSlug = $segments[0] ?? '';
        $splat = implode('/', array_slice($segments, 1));

        if ($typeSlug === '') {
            throw new PublicRecordNotFoundException('unknown', $splat !== '' ? $splat : 'unknown');
        }

        $entityType = $this->entityTypes->findBySlug($typeSlug);

        if ($entityType === null) {
            // Not a known content type → not a record permalink.
            throw new PublicRecordNotFoundException($typeSlug, $splat !== '' ? $splat : 'unknown');
        }

        $key = PublicPermalinkResolver::extractEntityKey($entityType->permalinkPattern, $splat);

        return $this->viewRenderer->renderEntity(
            $typeSlug,
            $key['entitySlug'],
            $key['entityId'],
            $request,
        );
    }
}
