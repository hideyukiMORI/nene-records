<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class GetAccessStatsByDateHandler
{
    public function __construct(
        private GetAccessStatsByDateUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $range = AccessStatsDateRangeParser::parse($request->getQueryParams());

        $output = $this->useCase->execute(new GetAccessStatsByDateInput(
            from: $range->from,
            to: $range->to,
        ));

        return $this->response->create([
            'from' => $output->from,
            'to' => $output->to,
            'items' => array_map(
                static fn (AccessStatsDayItem $item) => [
                    'date' => $item->date,
                    'request_count' => $item->requestCount,
                    'avg_duration_ms' => $item->avgDurationMs,
                ],
                $output->items,
            ),
        ]);
    }
}
