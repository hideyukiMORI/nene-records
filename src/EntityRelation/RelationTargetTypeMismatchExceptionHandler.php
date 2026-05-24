<?php

declare(strict_types=1);

namespace NeNeRecords\EntityRelation;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final readonly class RelationTargetTypeMismatchExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof RelationTargetTypeMismatchException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        return $this->problemDetails->create(
            $request,
            'relation-target-type-mismatch',
            'Relation Target Type Mismatch',
            422,
            'The target entity type does not match the relation field definition.',
        );
    }
}
