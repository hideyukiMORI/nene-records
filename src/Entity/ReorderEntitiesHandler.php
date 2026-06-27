<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * `POST /api/v1/entities/reorder` — set the manual sibling order from an ordered
 * list of record ids (`menu_order = position`). Used by directory drag / up-down
 * reordering (#659).
 */
final readonly class ReorderEntitiesHandler
{
    public function __construct(
        private ReorderEntitiesUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);
        $rawIds = $body['ids'] ?? null;

        if (!is_array($rawIds) || $rawIds === []) {
            throw new ValidationException([
                new ValidationError('ids', 'A non-empty ordered list of record ids is required.', 'required'),
            ]);
        }

        $ids = [];
        foreach ($rawIds as $rawId) {
            if (!is_int($rawId) || $rawId <= 0) {
                throw new ValidationException([
                    new ValidationError('ids', 'Each id must be a positive integer.', 'invalid'),
                ]);
            }
            $ids[] = $rawId;
        }

        $output = $this->useCase->execute(new ReorderEntitiesInput($ids));

        return $this->response->create(['reordered' => $output->reordered]);
    }
}
