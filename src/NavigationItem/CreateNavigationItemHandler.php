<?php

declare(strict_types=1);

namespace NeNeRecords\NavigationItem;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CreateNavigationItemHandler
{
    public function __construct(
        private CreateNavigationItemUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);
        $errors = [];

        $label = trim((string) ($body['label'] ?? ''));
        $url = trim((string) ($body['url'] ?? ''));
        $location = isset($body['location']) ? trim((string) $body['location']) : NavLocations::DEFAULT;
        $displayOrder = isset($body['display_order']) && is_int($body['display_order'])
            ? $body['display_order']
            : 0;

        if ($label === '') {
            $errors[] = new ValidationError('label', 'Label is required.', 'required');
        }

        if ($url === '') {
            $errors[] = new ValidationError('url', 'URL is required.', 'required');
        }

        if (!NavLocations::isValid($location)) {
            $errors[] = new ValidationError('location', 'Location must be one of: header, footer, side.', 'invalid');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $output = $this->useCase->execute(new CreateNavigationItemInput(
            label: $label,
            url: $url,
            location: $location,
            displayOrder: $displayOrder,
        ));

        return $this->response->create(NavigationItemHttpMapper::toArray($output->item), 201);
    }
}
