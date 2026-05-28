<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

/**
 * Worker that drains the webhook delivery queue (#285).
 *
 * For each due delivery it performs the HTTP send and then either marks it delivered,
 * reschedules it with exponential backoff, or marks it permanently failed once the
 * attempt budget is exhausted. Designed to be invoked repeatedly (e.g. by cron).
 */
final readonly class WebhookDeliveryProcessor
{
    private const BASE_BACKOFF_SECONDS = 60;
    private const MAX_BACKOFF_SECONDS = 3600;

    public function __construct(
        private WebhookDeliveryRepositoryInterface $deliveries,
        private WebhookSenderInterface $sender,
    ) {
    }

    /**
     * Process up to $limit due deliveries.
     *
     * @return array{processed: int, delivered: int, retried: int, failed: int}
     */
    public function process(int $limit = 50, ?int $now = null): array
    {
        $now ??= time();
        $due = $this->deliveries->claimDue($now, $limit);

        $delivered = 0;
        $retried = 0;
        $failed = 0;

        foreach ($due as $delivery) {
            $result = $this->sender->send($delivery->targetUrl, $delivery->secret, $delivery->payload);
            $attempts = $delivery->attempts + 1;

            if ($result->success) {
                $this->deliveries->markDelivered($delivery->id, $result->statusCode);
                $delivered++;

                continue;
            }

            if ($attempts >= $delivery->maxAttempts) {
                $this->deliveries->markFailed($delivery->id, $attempts, $result->error, $result->statusCode);
                $failed++;

                continue;
            }

            $this->deliveries->reschedule(
                $delivery->id,
                $attempts,
                $now + $this->backoffSeconds($attempts),
                $result->error,
                $result->statusCode,
            );
            $retried++;
        }

        return [
            'processed' => count($due),
            'delivered' => $delivered,
            'retried' => $retried,
            'failed' => $failed,
        ];
    }

    /** Exponential backoff: 60s, 120s, 240s, … capped at 1h. */
    private function backoffSeconds(int $attempts): int
    {
        $delay = self::BASE_BACKOFF_SECONDS * (2 ** ($attempts - 1));

        return (int) min($delay, self::MAX_BACKOFF_SECONDS);
    }
}
