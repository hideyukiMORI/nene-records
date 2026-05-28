<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final readonly class EmailVerificationTokenExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof EmailVerificationTokenException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        $expired = $exception instanceof EmailVerificationTokenException && $exception->expired;

        return $this->problemDetails->create(
            $request,
            $expired ? 'gone' : 'invalid-token',
            $expired ? 'Gone' : 'Unprocessable Entity',
            $expired ? 410 : 422,
            $exception->getMessage(),
        );
    }
}
