<?php

declare(strict_types=1);

namespace NeNeRecords\Menu;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class UpdateMenuHandler
{
    public function __construct(
        private UpdateMenuUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($parameters['id'] ?? 0);

        $body = JsonRequestBodyParser::parse($request);
        $errors = [];

        $name = trim((string) ($body['name'] ?? ''));
        $location = is_string($body['location'] ?? null) && trim((string) $body['location']) !== ''
            ? trim((string) $body['location'])
            : null;

        if ($name === '') {
            $errors[] = new ValidationError('name', 'Name is required.', 'required');
        }

        if (!MenuLocations::isValid($location)) {
            $errors[] = new ValidationError('location', 'Location must be header, footer, or null.', 'invalid');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $output = $this->useCase->execute(new UpdateMenuInput(id: $id, name: $name, location: $location));

        return $this->response->create(MenuHttpMapper::toArray($output->menu));
    }
}
