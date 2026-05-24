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
        $typeSlug = trim((string) ($parameters['slug'] ?? ''));
        $entitySlug = trim((string) ($parameters['entitySlug'] ?? ''));

        if ($typeSlug === '' || $entitySlug === '') {
            throw new PublicRecordNotFoundException(
                $typeSlug !== '' ? $typeSlug : 'unknown',
                $entitySlug !== '' ? $entitySlug : 'unknown',
            );
        }

        $output = $this->useCase->execute(new GetPublicRecordViewInput($typeSlug, $entitySlug));

        return $this->response->create($output->bootstrap);
    }
}
