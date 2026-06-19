<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Serves the in-band theme authoring guide (#440) so ClaudeDesign can learn the
 * manifest contract, rules, recipes and a valid example over MCP — without
 * reading the repository.
 */
final readonly class ThemeAuthoringGuideHandler
{
    public function __construct(
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->response->create(ThemeAuthoringGuide::build());
    }
}
