<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CreateThemeHandler
{
    public function __construct(
        private CreateThemeUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // The request body IS the theme manifest. The use case validates it
        // (ThemeManifestValidator) before persisting.
        $manifest = JsonRequestBodyParser::parse($request);

        $output = $this->useCase->execute(new CreateThemeInput(manifest: $manifest));

        return $this->response->create(ThemeHttpMapper::toArray($output->theme), 201);
    }
}
