<?php

declare(strict_types=1);

namespace NeNeRecords\Notification\Channel;

use NeNeRecords\Notification\NotificationChannelInterface;
use NeNeRecords\Notification\NotificationMessage;
use Throwable;

/**
 * ChatWork API v2 channel.
 *
 * config_json keys:
 *   api_token (string, required) — ChatWork API token
 *   room_id   (string, required) — ChatWork room ID (numeric)
 */
final readonly class ChatWorkChannel implements NotificationChannelInterface
{
    private const API_BASE = 'https://api.chatwork.com/v2';
    private const TIMEOUT_SECONDS = 5;

    public function send(NotificationMessage $message, array $config): void
    {
        try {
            $apiToken = isset($config['api_token']) && is_string($config['api_token'])
                ? $config['api_token']
                : '';
            $roomId = isset($config['room_id']) && (is_string($config['room_id']) || is_int($config['room_id']))
                ? (string) $config['room_id']
                : '';

            if ($apiToken === '' || $roomId === '') {
                return;
            }

            $body = "[info][title]{$message->title}[/title]{$message->body}";
            if ($message->url !== null) {
                $body .= "\n{$message->url}";
            }
            $body .= '[/info]';

            $this->postMessage($apiToken, $roomId, $body);
        } catch (Throwable) {
            // fire-and-forget
        }
    }

    private function postMessage(string $apiToken, string $roomId, string $body): void
    {
        $url = self::API_BASE . '/rooms/' . $roomId . '/messages';

        $ch = curl_init($url);
        if ($ch === false) {
            return;
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['body' => $body]),
            CURLOPT_HTTPHEADER => [
                'X-ChatWorkToken: ' . $apiToken,
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        curl_exec($ch);
        curl_close($ch);
    }
}
