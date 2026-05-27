<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class GetWebhookByIdHandler
{
    public function __construct(
        private GetWebhookByIdUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($parameters['id'] ?? 0);

        $output = $this->useCase->execute(new GetWebhookByIdInput($id));

        return $this->response->create(WebhookHttpMapper::toArray($output->webhook));
    }
}
