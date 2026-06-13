<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class GetPopularEntitiesHandler
{
    private const DEFAULT_DAYS = 30;
    private const MAX_DAYS = 366;
    private const DEFAULT_LIMIT = 5;
    private const MAX_LIMIT = 50;

    public function __construct(
        private GetPopularEntitiesUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();

        $days = $this->clampInt($params['days'] ?? null, self::DEFAULT_DAYS, 1, self::MAX_DAYS);
        $limit = $this->clampInt($params['limit'] ?? null, self::DEFAULT_LIMIT, 1, self::MAX_LIMIT);

        $output = $this->useCase->execute(new GetPopularEntitiesInput(days: $days, limit: $limit));

        return $this->response->create([
            'items' => array_map(
                static fn (PopularEntityItem $item) => [
                    'entity_id' => $item->entityId,
                    'entity_type_id' => $item->entityTypeId,
                    'slug' => $item->slug,
                    'published_at' => $item->publishedAtIso,
                    'title' => $item->title,
                    'view_count' => $item->viewCount,
                ],
                $output->items,
            ),
        ]);
    }

    private function clampInt(mixed $raw, int $default, int $min, int $max): int
    {
        if (!is_string($raw) || !ctype_digit($raw)) {
            return $default;
        }

        return max($min, min($max, (int) $raw));
    }
}
