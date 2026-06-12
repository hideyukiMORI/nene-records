<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final readonly class MediaInUseExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof MediaInUseException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        $usages = $exception instanceof MediaInUseException ? $exception->usages : [];

        return $this->problemDetails->create(
            $request,
            'media-in-use',
            'Media in use',
            409,
            $exception->getMessage(),
            ['usages' => array_map(static fn (MediaUsage $usage): array => [
                'entity_id' => $usage->entityId,
                'entity_type_slug' => $usage->entityTypeSlug,
                'entity_slug' => $usage->entitySlug,
                'status' => $usage->status,
                'field_key' => $usage->fieldKey,
                'title' => $usage->title,
            ], $usages)],
        );
    }
}
