<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class TestNotificationChannelHandler
{
    public function __construct(
        private TestNotificationChannelUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($params['id'] ?? 0);

        $this->useCase->execute(new TestNotificationChannelInput($id));

        return $this->response->create(['sent' => true]);
    }
}
