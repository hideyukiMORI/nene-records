<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

use Throwable;

/**
 * Delivers a webhook payload over HTTP via cURL (5 s timeout).
 *
 * A 2xx response is a success; any other status, transport error, or exception is a
 * failure that the {@see WebhookDeliveryProcessor} can retry.
 */
final readonly class CurlWebhookSender implements WebhookSenderInterface
{
    private const TIMEOUT_SECONDS = 5;

    public function send(string $url, ?string $secret, string $payload): WebhookSendResult
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'User-Agent: NeNeRecords-Webhook/1.0',
            ];

            if ($secret !== null && $secret !== '') {
                $headers[] = 'X-NeNe-Signature: sha256=' . hash_hmac('sha256', $payload, $secret);
            }

            $ch = curl_init($url);

            if ($ch === false) {
                return WebhookSendResult::failure('Failed to initialize cURL.');
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

            $result = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($result === false || $status === 0) {
                return WebhookSendResult::failure($error !== '' ? $error : 'Transport error.', null);
            }

            if ($status >= 200 && $status < 300) {
                return WebhookSendResult::ok($status);
            }

            return WebhookSendResult::failure('Non-2xx response.', $status);
        } catch (Throwable $e) {
            return WebhookSendResult::failure($e->getMessage());
        }
    }
}
