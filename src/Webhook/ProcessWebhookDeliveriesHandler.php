<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Cron-triggered entry point that drains the webhook delivery queue (#466).
 *
 * Mirrors the scheduled-publish processor: invoked by the cron container over
 * HTTP (no auth — see AdminApiAuthMiddleware::ALWAYS_OPEN_PREFIXES). The queue
 * is intentionally not organization-scoped, so a single call drains all tenants.
 */
final readonly class ProcessWebhookDeliveriesHandler
{
    private const DEFAULT_LIMIT = 50;
    private const MAX_LIMIT = 1000;

    public function __construct(
        private WebhookDeliveryProcessor $processor,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $summary = $this->processor->process($this->resolveLimit($request));

        return $this->response->create([
            'processed' => $summary['processed'],
            'delivered' => $summary['delivered'],
            'retried' => $summary['retried'],
            'failed' => $summary['failed'],
        ]);
    }

    private function resolveLimit(ServerRequestInterface $request): int
    {
        $query = $request->getQueryParams();
        $raw = $query['limit'] ?? null;

        if (!is_scalar($raw)) {
            return self::DEFAULT_LIMIT;
        }

        $limit = (int) $raw;

        if ($limit < 1) {
            return self::DEFAULT_LIMIT;
        }

        return min($limit, self::MAX_LIMIT);
    }
}
