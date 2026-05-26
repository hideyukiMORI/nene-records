<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CreateEntityTypeHandler
{
    private const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public function __construct(
        private CreateEntityTypeUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);

        $errors = [];

        $name = trim((string) ($body['name'] ?? ''));
        $slug = trim((string) ($body['slug'] ?? ''));
        $isPinned = (bool) ($body['is_pinned'] ?? false);

        if ($name === '') {
            $errors[] = new ValidationError('name', 'Name is required.', 'required');
        }

        if ($slug === '') {
            $errors[] = new ValidationError('slug', 'Slug is required.', 'required');
        } elseif (preg_match(self::SLUG_PATTERN, $slug) !== 1) {
            $errors[] = new ValidationError('slug', 'Slug format is invalid.', 'format');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $output = $this->useCase->execute(new CreateEntityTypeInput(name: $name, slug: $slug, isPinned: $isPinned));

        return $this->response->create(
            ['id' => $output->id, 'name' => $output->name, 'slug' => $output->slug, 'is_pinned' => $output->isPinned],
            201,
            ['Location' => '/api/v1/entity-types/' . $output->id],
        );
    }
}
