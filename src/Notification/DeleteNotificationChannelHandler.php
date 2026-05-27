<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

use Nene2\Routing\Router;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DeleteNotificationChannelHandler
{
    public function __construct(
        private DeleteNotificationChannelUseCaseInterface $useCase,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($params['id'] ?? 0);

        $this->useCase->execute(new DeleteNotificationChannelInput($id));

        return $this->responseFactory->createResponse(204);
    }
}
