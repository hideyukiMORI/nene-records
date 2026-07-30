<?php

declare(strict_types=1);

namespace NeNeRecords\OrgConnect;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListConnectTokensHandler
{
    public function __construct(
        private ListConnectTokensUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $output = $this->useCase->execute();

        return $this->response->create([
            'items' => array_map(
                static fn (ConnectTokenSummary $summary) => ConnectTokenHttpMapper::toArray($summary),
                $output->items,
            ),
        ]);
    }
}
