<?php

declare(strict_types=1);

namespace NeNeRecords\PreviewToken;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeNeRecords\Entity\EntityNotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class GeneratePreviewTokenHandler
{
    public function __construct(
        private GeneratePreviewTokenUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($parameters['id'] ?? 0);

        if ($id <= 0) {
            throw new EntityNotFoundException($id);
        }

        $output = $this->useCase->execute(new GeneratePreviewTokenInput(entityId: $id));

        return $this->response->create(
            [
                'token' => $output->token,
                'expires_at' => $output->expiresAtIso,
                'preview_url' => $output->previewUrl,
            ],
            201,
        );
    }
}
