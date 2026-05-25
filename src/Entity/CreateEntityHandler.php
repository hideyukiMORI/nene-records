<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CreateEntityHandler
{
    public function __construct(
        private CreateEntityUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);

        $errors = [];

        $rawTypeId = $body['entity_type_id'] ?? null;
        $entityTypeId = is_int($rawTypeId)
            ? $rawTypeId
            : filter_var(is_string($rawTypeId) ? $rawTypeId : null, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);

        if ($entityTypeId === null || $entityTypeId <= 0) {
            $errors[] = new ValidationError('entity_type_id', 'Entity type id is required and must be a positive integer.', 'required');
        }

        $rawSlug = $body['slug'] ?? null;
        $slug = is_string($rawSlug) && trim($rawSlug) !== '' ? trim($rawSlug) : null;

        $rawStatus = $body['status'] ?? null;
        $status = is_string($rawStatus) ? EntityStatus::tryFrom($rawStatus) ?? EntityStatus::Draft : EntityStatus::Draft;

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        /** @var int $entityTypeId */
        $output = $this->useCase->execute(new CreateEntityInput(
            entityTypeId: $entityTypeId,
            slug: $slug,
            status: $status,
        ));

        return $this->response->create(
            [
                'id' => $output->id,
                'entity_type_id' => $output->entityTypeId,
                'slug' => $output->slug,
                'status' => $output->status,
                'published_at' => $output->publishedAtIso,
                'is_deleted' => $output->isDeleted,
                'deleted_at' => $output->deletedAtIso,
            ],
            201,
            ['Location' => '/api/v1/entities/' . $output->id],
        );
    }
}
