<?php

declare(strict_types=1);

namespace NeNeRecords\Tag;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CreateTagHandler
{
    private const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public function __construct(
        private CreateTagUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);

        $errors = [];

        $name = trim((string) ($body['name'] ?? ''));
        $slug = trim((string) ($body['slug'] ?? ''));

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

        $output = $this->useCase->execute(new CreateTagInput(name: $name, slug: $slug));

        return $this->response->create(
            ['id' => $output->id, 'slug' => $output->slug, 'name' => $output->name],
            201,
            ['Location' => '/api/v1/tags/' . $output->id],
        );
    }
}
