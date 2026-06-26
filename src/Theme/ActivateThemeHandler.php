<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ActivateThemeHandler
{
    public function __construct(
        private ActivateThemeUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $key = (string) ($parameters['key'] ?? '');

        $output = $this->useCase->execute(new ActivateThemeInput(themeKey: $key));

        return $this->response->create(['active_theme' => $output->activeTheme]);
    }
}
