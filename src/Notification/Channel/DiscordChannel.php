<?php

declare(strict_types=1);

namespace NeNeRecords\Notification\Channel;

use NeNeRecords\Notification\NotificationChannelInterface;
use NeNeRecords\Notification\NotificationMessage;
use Throwable;

/**
 * Discord Webhook channel.
 *
 * config_json keys:
 *   webhook_url (string, required) — Discord webhook URL
 */
final readonly class DiscordChannel implements NotificationChannelInterface
{
    private const TIMEOUT_SECONDS = 5;

    public function send(NotificationMessage $message, array $config): void
    {
        try {
            $webhookUrl = isset($config['webhook_url']) && is_string($config['webhook_url'])
                ? $config['webhook_url']
                : '';

            if ($webhookUrl === '') {
                return;
            }

            $content = "**{$message->title}**\n{$message->body}";
            if ($message->url !== null) {
                $content .= "\n{$message->url}";
            }

            // Discord limits content to 2000 chars
            if (mb_strlen($content) > 2000) {
                $content = mb_substr($content, 0, 1997) . '...';
            }

            $payload = json_encode(['content' => $content], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

            $this->post($webhookUrl, $payload);
        } catch (Throwable) {
            // fire-and-forget
        }
    }

    private function post(string $url, string $payload): void
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return;
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        curl_exec($ch);
        curl_close($ch);
    }
}
