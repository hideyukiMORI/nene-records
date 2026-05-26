<?php

declare(strict_types=1);

namespace NeNeRecords\PreviewToken;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class GetPreviewRecordViewHandler
{
    public function __construct(
        private GetPreviewRecordViewUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $token = trim((string) ($parameters['token'] ?? ''));

        if ($token === '') {
            throw new PreviewTokenNotFoundException('');
        }

        $output = $this->useCase->execute(new GetPreviewRecordViewInput($token));

        return $this->response->create($output->bootstrap);
    }
}
