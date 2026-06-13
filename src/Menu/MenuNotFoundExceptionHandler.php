<?php

declare(strict_types=1);

namespace NeNeRecords\Menu;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class MenuNotFoundExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(\Throwable $e): bool
    {
        return $e instanceof MenuNotFoundException;
    }

    public function handle(\Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        return $this->problemDetails->create($request, 'not-found', 'Not Found', 404, $exception->getMessage());
    }
}
