<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class GetThemeHandler
{
    public function __construct(
        private ThemeRepositoryInterface $repository,
        private JsonResponseFactory $response,
        private ThemeThumbnailResolver $thumbnails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $key = (string) ($parameters['key'] ?? '');

        $theme = $this->repository->findByKey($key);

        if ($theme === null) {
            throw new ThemeNotFoundException($key);
        }

        return $this->response->create(
            ThemeHttpMapper::toArray($theme, $this->thumbnails->resolve($theme->manifest)),
        );
    }
}
