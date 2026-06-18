<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListThemesHandler
{
    public function __construct(
        private ListThemesUseCaseInterface $useCase,
        private JsonResponseFactory $response,
        private ThemeThumbnailResolver $thumbnails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $output = $this->useCase->execute();

        return $this->response->create([
            'items' => array_map(
                fn (Theme $theme) => ThemeHttpMapper::toArray($theme, $this->thumbnails->resolve($theme->manifest)),
                $output->items,
            ),
        ]);
    }
}
