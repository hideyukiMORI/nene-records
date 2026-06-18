<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class UpdateThemeHandler
{
    public function __construct(
        private UpdateThemeUseCaseInterface $useCase,
        private JsonResponseFactory $response,
        private ThemeThumbnailResolver $thumbnails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $key = (string) ($parameters['key'] ?? '');

        $manifest = JsonRequestBodyParser::parse($request);

        $output = $this->useCase->execute(new UpdateThemeInput(themeKey: $key, manifest: $manifest));

        return $this->response->create(
            ThemeHttpMapper::toArray($output->theme, $this->thumbnails->resolve($output->theme->manifest)),
        );
    }
}
