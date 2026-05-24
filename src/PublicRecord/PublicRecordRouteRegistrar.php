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
    ) {
    }

    public function __invoke(Router $router): void
    {
        $getPublicRecordViewHandler = $this->getPublicRecordViewHandler;
        $renderPublicRecordViewHandler = $this->renderPublicRecordViewHandler;

        $router->get(
            '/api/v1/public/entity-types/{slug}/records/{entityId}',
            static fn (ServerRequestInterface $request) => $getPublicRecordViewHandler->handle($request),
        );

        $router->get(
            '/view/{slug}/{entityId}',
            static fn (ServerRequestInterface $request) => $renderPublicRecordViewHandler->handle($request),
        );
    }
}
