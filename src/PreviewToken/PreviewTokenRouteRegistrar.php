<?php

declare(strict_types=1);

namespace NeNeRecords\PreviewToken;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class PreviewTokenRouteRegistrar
{
    public function __construct(
        private GeneratePreviewTokenHandler $generateHandler,
        private RevokePreviewTokenHandler $revokeHandler,
        private GetPreviewRecordViewHandler $getHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $generateHandler = $this->generateHandler;
        $revokeHandler = $this->revokeHandler;
        $getHandler = $this->getHandler;

        $router->post(
            '/api/v1/entities/{id}/preview-token',
            static fn (ServerRequestInterface $request) => $generateHandler->handle($request),
        );

        $router->delete(
            '/api/v1/entities/{id}/preview-token',
            static fn (ServerRequestInterface $request) => $revokeHandler->handle($request),
        );

        $router->get(
            '/api/v1/public/preview/{token}',
            static fn (ServerRequestInterface $request) => $getHandler->handle($request),
        );
    }
}
