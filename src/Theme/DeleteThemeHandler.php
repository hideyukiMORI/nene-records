<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DeleteThemeHandler
{
    public function __construct(
        private DeleteThemeUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $key = (string) ($parameters['key'] ?? '');

        $this->useCase->execute(new DeleteThemeInput($key));

        return $this->response->createEmpty(204);
    }
}
