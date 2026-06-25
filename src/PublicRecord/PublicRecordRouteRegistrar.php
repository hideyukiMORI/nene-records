<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class PublicRecordRouteRegistrar
{
    public function __construct(
        private GetPublicRecordViewHandler $getPublicRecordViewHandler,
        private RenderPublicRecordViewHandler $renderPublicRecordViewHandler,
        private RenderPublicPermalinkHandler $renderPublicPermalinkHandler,
        private RenderSitemapHandler $renderSitemapHandler,
        private RenderRobotsHandler $renderRobotsHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $getPublicRecordViewHandler = $this->getPublicRecordViewHandler;
        $renderPublicRecordViewHandler = $this->renderPublicRecordViewHandler;
        $renderPublicPermalinkHandler = $this->renderPublicPermalinkHandler;
        $renderSitemapHandler = $this->renderSitemapHandler;
        $renderRobotsHandler = $this->renderRobotsHandler;

        $router->get(
            '/api/v1/public/entity-types/{slug}/records/{entitySlug}',
            static fn (ServerRequestInterface $request) => $getPublicRecordViewHandler->handle($request),
        );

        // Per-org XML sitemap. A registered route returns 200, so the 301 / SPA-shell
        // fallback layers (which act only on 404) never intercept it.
        $router->get(
            '/sitemap.xml',
            static fn (ServerRequestInterface $request) => $renderSitemapHandler->handle($request),
        );

        $router->get(
            '/robots.txt',
            static fn (ServerRequestInterface $request) => $renderRobotsHandler->handle($request),
        );

        $router->get(
            '/view/{slug}/{entitySlug}',
            static fn (ServerRequestInterface $request) => $renderPublicRecordViewHandler->handle($request),
        );

        // Real-permalink crawlable HTML (single-origin). These max-param patterns
        // cover the built-in permalink presets ({type}/{id}, {type}/{slug},
        // {type}/{y}/{m}/{slug}, {type}/{y}/{m}/{d}/{slug}); the router's
        // param-count sort guarantees any more-specific route (api/media/view) wins.
        $permalink = static fn (ServerRequestInterface $request) => $renderPublicPermalinkHandler->handle($request);
        $router->get('/{p0}/{p1}', $permalink);
        $router->get('/{p0}/{p1}/{p2}/{p3}', $permalink);
        $router->get('/{p0}/{p1}/{p2}/{p3}/{p4}', $permalink);
    }
}
