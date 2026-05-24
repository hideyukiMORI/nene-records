<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final readonly class EntityTypeSlugConflictExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof EntityTypeSlugConflictException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        return $this->problemDetails->create(
            $request,
            'conflict',
            'Conflict',
            409,
            'The slug is already in use.',
        );
    }
}
