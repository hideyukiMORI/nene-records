<?php

declare(strict_types=1);

namespace NeNeRecords\OrgConnect;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DeleteConnectTokenHandler
{
    public function __construct(
        private DeleteConnectTokenUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $service = ConnectTokenServiceResolver::fromPath($request);

        $this->useCase->execute(new DeleteConnectTokenInput($service));

        return $this->response->createEmpty(204);
    }
}
