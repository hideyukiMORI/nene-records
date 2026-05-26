<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

use Throwable;

/**
 * Sends webhook payloads via cURL (fire-and-forget, 5 s timeout).
 */
final readonly class CurlWebhookDispatcher implements WebhookDispatcherInterface
{
    private const TIMEOUT_SECONDS = 5;

    public function __construct(
        private WebhookRepositoryInterface $webhooks,
    ) {
    }

    public function dispatch(string $event, int $entityTypeId, int $entityId): void
    {
        try {
            $matching = $this->webhooks->findActiveByEventAndEntityTypeId($event, $entityTypeId);
        } catch (Throwable) {
            return;
        }

        if ($matching === []) {
            return;
        }

        $payload = json_encode([
            'event' => $event,
            'entity_type_id' => $entityTypeId,
            'entity_id' => $entityId,
            'occurred_at' => date('c'),
        ], JSON_THROW_ON_ERROR);

        foreach ($matching as $webhook) {
            $this->send($webhook, $payload);
        }
    }

    private function send(Webhook $webhook, string $payload): void
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'User-Agent: NeNeRecords-Webhook/1.0',
            ];

            if ($webhook->secret !== null && $webhook->secret !== '') {
                $sig = hash_hmac('sha256', $payload, $webhook->secret);
                $headers[] = 'X-NeNe-Signature: sha256=' . $sig;
            }

            $ch = curl_init($webhook->url);

            if ($ch === false) {
                return;
            }

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
                CURLOPT_CONNECTTIMEOUT => self::TIMEOUT_SECONDS,
                CURLOPT_FOLLOWLOCATION => false,
            ]);

            curl_exec($ch);
            curl_close($ch);
        } catch (Throwable) {
            // Never let a Webhook failure surface to the caller
        }
    }
}
