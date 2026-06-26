<?php

declare(strict_types=1);

namespace NeNeRecords\Entitlement;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Maps {@see FeatureNotEntitledException} to 402 Payment Required — the
 * semantically apt status for "this needs a higher plan".
 */
final readonly class FeatureNotEntitledExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof FeatureNotEntitledException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        return $this->problemDetails->create(
            $request,
            'feature-not-entitled',
            'Feature Not Available',
            402,
            $exception->getMessage(),
        );
    }
}
