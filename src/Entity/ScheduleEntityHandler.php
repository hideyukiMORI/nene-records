<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ScheduleEntityHandler
{
    public function __construct(
        private ScheduleEntityUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($parameters['id'] ?? 0);

        if ($id <= 0) {
            throw new EntityNotFoundException($id);
        }

        $body = JsonRequestBodyParser::parse($request);

        $rawScheduledAt = $body['scheduled_at'] ?? null;
        $scheduledAt = is_string($rawScheduledAt) ? (DateTimeImmutable::createFromFormat(DATE_ATOM, $rawScheduledAt) ?: null) : null;

        if ($scheduledAt === null) {
            throw new ValidationException([
                new ValidationError('scheduled_at', 'scheduled_at is required and must be a valid ISO 8601 datetime.', 'required'),
            ]);
        }

        try {
            $output = $this->useCase->execute(new ScheduleEntityInput(
                id: $id,
                scheduledAt: $scheduledAt,
            ));
        } catch (InvalidArgumentException $e) {
            throw new ValidationException([
                new ValidationError('scheduled_at', $e->getMessage(), 'invalid'),
            ]);
        }

        return $this->response->create([
            'id' => $output->id,
            'status' => $output->status,
            'scheduled_at' => $output->scheduledAtIso,
        ]);
    }
}
