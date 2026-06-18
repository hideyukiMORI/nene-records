<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Computed theme preview (#433): the request body is a manifest; returns the
 * contrast/quality report without persisting. Always 200 (issues are reported
 * in the body), so ClaudeDesign can iterate before committing via createTheme.
 */
final readonly class PreviewThemeHandler
{
    public function __construct(
        private PreviewThemeUseCase $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $manifest = JsonRequestBodyParser::parse($request);

        $output = $this->useCase->execute(new PreviewThemeInput(manifest: $manifest));

        return $this->response->create($output->toArray());
    }
}
