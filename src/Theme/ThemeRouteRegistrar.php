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
        private ListPublicThemesHandler $listPublicHandler,
        private ThemeAuthoringGuideHandler $authoringGuideHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $list = $this->listHandler;
        $get = $this->getHandler;
        $create = $this->createHandler;
        $update = $this->updateHandler;
        $delete = $this->deleteHandler;
        $listPublic = $this->listPublicHandler;
        $authoringGuide = $this->authoringGuideHandler;

        // In-band authoring guide for MCP agents (#440). The router prioritises
        // this static path over /themes/{key} (fewer path params win).
        $router->get(
            '/api/v1/themes/authoring-guide',
            static fn (ServerRequestInterface $request) => $authoringGuide->handle($request),
        );
        $router->get(
            '/api/v1/themes',
            static fn (ServerRequestInterface $request) => $list->handle($request),
        );
        $router->post(
            '/api/v1/themes',
            static fn (ServerRequestInterface $request) => $create->handle($request),
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
        // Public read (open via ALWAYS_OPEN_PREFIXES /api/v1/public/) — the
        // public site applies a runtime active theme from this.
        $router->get(
            '/api/v1/public/themes',
            static fn (ServerRequestInterface $request) => $listPublic->handle($request),
        );
    }
}
