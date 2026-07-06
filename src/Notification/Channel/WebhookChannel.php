<?php

declare(strict_types=1);

namespace NeNeRecords\Notification\Channel;

use Nene2\Http\ClockInterface;
use NeNeRecords\Notification\NotificationChannelInterface;
use NeNeRecords\Notification\NotificationMessage;
use Throwable;

/**
 * Generic HTTP webhook channel (POST JSON).
 *
 * config_json keys:
 *   url          (string, required)              — endpoint URL
 *   headers_json (string|null, optional)         — JSON object of extra headers
 */
final readonly class WebhookChannel implements NotificationChannelInterface
{
    private const TIMEOUT_SECONDS = 5;

    public function __construct(
        private ClockInterface $clock,
    ) {
    }

    public function send(NotificationMessage $message, array $config): void
    {
        try {
            $url = isset($config['url']) && is_string($config['url']) ? $config['url'] : '';

            if ($url === '') {
                return;
            }

            $payload = json_encode([
                'event' => $message->event,
                'title' => $message->title,
                'body' => $message->body,
                'url' => $message->url,
                'occurred_at' => $this->clock->now()->format('c'),
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

            $extraHeaders = $this->parseHeaders($config);

            $this->post($url, $payload, $extraHeaders);
        } catch (Throwable) {
            // fire-and-forget
        }
    }

    /**
     * @param array<string,mixed> $config
     * @return string[]
     */
    private function parseHeaders(array $config): array
    {
        if (!isset($config['headers_json']) || !is_string($config['headers_json']) || $config['headers_json'] === '') {
            return [];
        }

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($config['headers_json'], true, 8, JSON_THROW_ON_ERROR);

            if (!is_array($decoded)) {
                return [];
            }

            $result = [];
            foreach ($decoded as $name => $value) {
                if (is_string($name) && (is_string($value) || is_int($value))) {
                    $result[] = $name . ': ' . $value;
                }
            }

            return $result;
        } catch (Throwable) {
            return [];
        }
    }

    /** @param string[] $extraHeaders */
    private function post(string $url, string $payload, array $extraHeaders): void
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return;
        }

        $headers = array_merge(
            [
                'Content-Type: application/json',
                'User-Agent: NeNeRecords-Notification/1.0',
            ],
            $extraHeaders,
        );

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
    }
}
