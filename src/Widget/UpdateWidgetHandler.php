<?php

declare(strict_types=1);

namespace NeNeRecords\Widget;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class UpdateWidgetHandler
{
    public function __construct(
        private UpdateWidgetUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($parameters['id'] ?? 0);

        if ($id <= 0) {
            throw new WidgetNotFoundException($id);
        }

        $body = JsonRequestBodyParser::parse($request);
        $parsed = WidgetRequestParser::parse($body);

        if ($parsed->errors !== []) {
            throw new ValidationException($parsed->errors);
        }

        $output = $this->useCase->execute(new UpdateWidgetInput(
            id: $id,
            widgetType: $parsed->widgetType,
            region: $parsed->region,
            displayOrder: $parsed->displayOrder,
            title: $parsed->title,
            settings: $parsed->settings,
        ));

        return $this->response->create(WidgetHttpMapper::toArray($output->item));
    }
}
