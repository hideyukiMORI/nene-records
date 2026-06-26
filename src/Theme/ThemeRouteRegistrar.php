<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ThemeRouteRegistrar
{
    public function __construct(
        private ListThemesHandler $listHandler,
        private GetThemeHandler $getHandler,
        private CreateThemeHandler $createHandler,
        private UpdateThemeHandler $updateHandler,
        private DeleteThemeHandler $deleteHandler,
        private ActivateThemeHandler $activateHandler,
        private ListPublicThemesHandler $listPublicHandler,
        private PreviewThemeHandler $previewHandler,
        private ThemeAuthoringGuideHandler $authoringGuideHandler,
        private ThemeEngineCssHandler $engineCssHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $list = $this->listHandler;
        $get = $this->getHandler;
        $create = $this->createHandler;
        $update = $this->updateHandler;
        $delete = $this->deleteHandler;
        $activate = $this->activateHandler;
        $listPublic = $this->listPublicHandler;
        $preview = $this->previewHandler;
        $authoringGuide = $this->authoringGuideHandler;
        $engineCss = $this->engineCssHandler;

        // In-band authoring guide for MCP agents (#440). The router prioritises
        // these static paths over /themes/{key} (fewer path params win).
        $router->get(
            '/api/v1/themes/authoring-guide',
            static fn (ServerRequestInterface $request) => $authoringGuide->handle($request),
        );
        // Deployed base engine CSS (flag implementations) for exact authoring (#448).
        $router->get(
            '/api/v1/themes/engine-css',
            static fn (ServerRequestInterface $request) => $engineCss->handle($request),
        );
        $router->get(
            '/api/v1/themes',
            static fn (ServerRequestInterface $request) => $list->handle($request),
        );
        $router->post(
            '/api/v1/themes',
            static fn (ServerRequestInterface $request) => $create->handle($request),
        );
        // Computed preview (non-persistent dry-run): contrast/quality report.
        $router->post(
            '/api/v1/themes/preview',
            static fn (ServerRequestInterface $request) => $preview->handle($request),
        );
        $router->get(
            '/api/v1/themes/{key}',
            static fn (ServerRequestInterface $request) => $get->handle($request),
        );
        $router->put(
            '/api/v1/themes/{key}',
            static fn (ServerRequestInterface $request) => $update->handle($request),
        );
        $router->delete(
            '/api/v1/themes/{key}',
            static fn (ServerRequestInterface $request) => $delete->handle($request),
        );
        // Make a theme live for the org (verifies existence, then sets active_theme).
        $router->post(
            '/api/v1/themes/{key}/activate',
            static fn (ServerRequestInterface $request) => $activate->handle($request),
        );
        // Public read (open via ALWAYS_OPEN_PREFIXES /api/v1/public/) — the
        // public site applies a runtime active theme from this.
        $router->get(
            '/api/v1/public/themes',
            static fn (ServerRequestInterface $request) => $listPublic->handle($request),
        );
    }
}
