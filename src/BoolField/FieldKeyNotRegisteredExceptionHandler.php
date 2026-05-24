<?php

declare(strict_types=1);

namespace NeNeRecords\BoolField;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final readonly class FieldKeyNotRegisteredExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof FieldKeyNotRegisteredException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        return $this->problemDetails->create(
            $request,
            'field-key-not-registered',
            'Field Key Not Registered',
            422,
            'The field key is not registered for this entity type.',
        );
    }
}
