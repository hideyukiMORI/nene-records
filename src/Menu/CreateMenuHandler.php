<?php

declare(strict_types=1);

namespace NeNeRecords\Menu;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CreateMenuHandler
{
    public function __construct(
        private CreateMenuUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);
        $errors = [];

        $name = trim((string) ($body['name'] ?? ''));
        $location = $this->parseLocation($body['location'] ?? null);

        if ($name === '') {
            $errors[] = new ValidationError('name', 'Name is required.', 'required');
        }

        if (!MenuLocations::isValid($location)) {
            $errors[] = new ValidationError('location', 'Location must be header, footer, or null.', 'invalid');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $output = $this->useCase->execute(new CreateMenuInput(name: $name, location: $location));

        return $this->response->create(MenuHttpMapper::toArray($output->menu), 201);
    }

    private function parseLocation(mixed $raw): ?string
    {
        if (!is_string($raw)) {
            return null;
        }
        $value = trim($raw);

        return $value === '' ? null : $value;
    }
}
