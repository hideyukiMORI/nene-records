<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ReorderEntityTypesHandler
{
    public function __construct(
        private ReorderEntityTypesUseCaseInterface $useCase,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);

        $rawIds = $body['ids'] ?? null;
        if (!is_array($rawIds)) {
            throw new ValidationException([
                new ValidationError('ids', 'ids must be an array of entity type ids.', 'required'),
            ]);
        }

        $ids = [];
        foreach (array_values($rawIds) as $value) {
            if (!is_int($value) && !(is_string($value) && ctype_digit($value))) {
                throw new ValidationException([
                    new ValidationError('ids', 'ids must contain integers only.', 'format'),
                ]);
            }
            $ids[] = (int) $value;
        }

        $this->useCase->execute(new ReorderEntityTypesInput($ids));

        return $this->responseFactory->createResponse(204);
    }
}
