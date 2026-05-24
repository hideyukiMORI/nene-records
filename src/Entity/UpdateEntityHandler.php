<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use DateTimeImmutable;
use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class UpdateEntityHandler
{
    public function __construct(
        private UpdateEntityUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($parameters['id'] ?? 0);

        if ($id <= 0) {
            throw new EntityNotFoundException($id);
        }

        $body = JsonRequestBodyParser::parse($request);

        $errors = [];

        $rawTypeId = $body['entity_type_id'] ?? null;
        $entityTypeId = is_int($rawTypeId)
            ? $rawTypeId
            : filter_var(is_string($rawTypeId) ? $rawTypeId : null, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);

        if ($entityTypeId === null || $entityTypeId <= 0) {
            $errors[] = new ValidationError('entity_type_id', 'Entity type id is required and must be a positive integer.', 'required');
        }

        $rawStatus = $body['status'] ?? null;
        $status = is_string($rawStatus) && EntityStatus::isValid($rawStatus) ? $rawStatus : EntityStatus::DRAFT;

        $rawPublishedAt = $body['published_at'] ?? null;
        $publishedAt = is_string($rawPublishedAt) ? (DateTimeImmutable::createFromFormat(DATE_ATOM, $rawPublishedAt) ?: null) : null;

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        /** @var int $entityTypeId */
        $output = $this->useCase->execute(new UpdateEntityInput(
            id: $id,
            entityTypeId: $entityTypeId,
            status: $status,
            publishedAt: $publishedAt,
        ));

        return $this->response->create([
            'id' => $output->id,
            'entity_type_id' => $output->entityTypeId,
            'status' => $output->status,
            'published_at' => $output->publishedAtIso,
            'is_deleted' => $output->isDeleted,
            'deleted_at' => $output->deletedAtIso,
        ]);
    }
}
