<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ProcessScheduledPublishHandler
{
    public function __construct(
        private ProcessScheduledPublishUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $output = $this->useCase->execute();

        return $this->response->create([
            'published_count' => count($output->publishedIds),
            'published_ids' => $output->publishedIds,
        ]);
    }
}
