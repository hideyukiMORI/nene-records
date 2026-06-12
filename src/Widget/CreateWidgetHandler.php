<?php

declare(strict_types=1);

namespace NeNeRecords\Widget;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CreateWidgetHandler
{
    public function __construct(
        private CreateWidgetUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);
        $parsed = WidgetRequestParser::parse($body);

        if ($parsed->errors !== []) {
            throw new ValidationException($parsed->errors);
        }

        $output = $this->useCase->execute(new CreateWidgetInput(
            widgetType: $parsed->widgetType,
            region: $parsed->region,
            displayOrder: $parsed->displayOrder,
            title: $parsed->title,
            settings: $parsed->settings,
        ));

        return $this->response->create(WidgetHttpMapper::toArray($output->item), 201);
    }
}
