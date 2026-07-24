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
            'visitor' => $this->serializeVisitor($output->visitor),
        ]);
    }

    /**
     * @return array{
     *     unique_visitors: int, bot_rate: ?float,
     *     top_referrers: list<array{host: string, count: int}>,
     *     utm: list<array{source: ?string, medium: ?string, campaign: ?string, count: int}>,
     *     ref: list<array{ref: string, count: int}>
     * }|null
     */
    private function serializeVisitor(?VisitorSummary $visitor): ?array
    {
        if ($visitor === null) {
            return null;
        }

        return [
            'unique_visitors' => $visitor->uniqueVisitors,
            'bot_rate' => $visitor->botRate,
            'top_referrers' => $visitor->topReferrers,
            'utm' => $visitor->utm,
            'ref' => $visitor->ref,
        ];
    }
}
