<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final readonly class MediaInvalidTypeExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof MediaInvalidTypeException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        return $this->problemDetails->create(
            $request,
            'unprocessable-entity',
            'Unprocessable Entity',
            422,
            'Unsupported media type. Allowed: JPEG, PNG, GIF, WebP, SVG, PDF.',
        );
    }
}
