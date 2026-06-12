<?php

declare(strict_types=1);

namespace NeNeRecords\Widget;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DeleteWidgetHandler
{
    public function __construct(
        private DeleteWidgetUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($parameters['id'] ?? 0);

        $this->useCase->execute(new DeleteWidgetInput($id));

        return $this->response->createEmpty(204);
    }
}
