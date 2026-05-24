<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class GetPublicRecordViewHandler
{
    public function __construct(
        private GetPublicRecordViewUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $slug = trim((string) ($parameters['slug'] ?? ''));
        $entityId = (int) ($parameters['entityId'] ?? 0);

        if ($slug === '' || $entityId <= 0) {
            throw new PublicRecordNotFoundException($slug !== '' ? $slug : 'unknown', max(0, $entityId));
        }

        $output = $this->useCase->execute(new GetPublicRecordViewInput($slug, $entityId));

        return $this->response->create($output->bootstrap);
    }
}
