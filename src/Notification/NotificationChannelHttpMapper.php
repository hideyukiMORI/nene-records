<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

/**
 * Serialises a {@see NotificationChannel} for read responses while keeping the
 * capability secrets inside `config` write-only (#845, same contract as the
 * webhook signing secret in #836 / #824).
 *
 * A channel's `config` carries per-type settings. Some of those keys are
 * capability secrets — a Slack/Discord Incoming Webhook URL can post to the
 * workspace on its own, a ChatWork API token can act as the account, a generic
 * webhook URL / extra headers can carry bearer credentials. Those keys are
 * never echoed on read; the response only advertises whether one is configured
 * via a `has_<key>` boolean placed inside `config`. Non-sensitive keys
 * (recipient address, room id, …) are returned verbatim.
 */
final class NotificationChannelHttpMapper
{
    /**
     * Capability-secret config keys per channel type. Only these are stripped
     * from read responses and preserved on omitted updates.
     *
     * @var array<string, list<string>>
     */
    private const SENSITIVE_KEYS = [
        'email' => [],
        'slack' => ['webhook_url'],
        'discord' => ['webhook_url'],
        'chatwork' => ['api_token'],
        'webhook' => ['url', 'headers_json'],
    ];

    /**
     * @return list<string>
     */
    public static function sensitiveKeys(string $channelType): array
    {
        return self::SENSITIVE_KEYS[$channelType] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public static function toArray(NotificationChannel $channel): array
    {
        return [
            'id'           => $channel->id,
            'channel_type' => $channel->channelType,
            'label'        => $channel->label,
            'is_enabled'   => $channel->isEnabled,
            'config'       => self::redactConfig($channel->channelType, $channel->config),
            'created_at'   => $channel->createdAt,
            'updated_at'   => $channel->updatedAt,
        ];
    }

    /**
     * Strip capability secrets from a config for read exposure and add a
     * `has_<key>` flag for each so callers can tell whether one is configured.
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public static function redactConfig(string $channelType, array $config): array
    {
        $sensitive = self::sensitiveKeys($channelType);

        $result = [];
        foreach ($config as $key => $value) {
            if (in_array($key, $sensitive, true)) {
                continue; // never echo the secret itself
            }
            $result[$key] = $value;
        }

        foreach ($sensitive as $key) {
            // isset() already treats a null value as absent.
            $result['has_' . $key] = isset($config[$key]) && $config[$key] !== '';
        }

        return $result;
    }

    /**
     * Merge an incoming (write) config with the stored one so that an omitted
     * (missing / null / empty) capability secret keeps its existing value and a
     * provided one replaces it. Any `has_<key>` mirror keys the client echoed
     * back are dropped so they never get persisted.
     *
     * @param array<string, mixed> $existing
     * @param array<string, mixed> $incoming
     * @return array<string, mixed>
     */
    public static function mergeConfigForUpdate(string $channelType, array $existing, array $incoming): array
    {
        $sensitive = self::sensitiveKeys($channelType);

        $result = [];
        foreach ($incoming as $key => $value) {
            if (str_starts_with($key, 'has_')) {
                continue; // read-only mirror flag, never stored
            }
            $result[$key] = $value;
        }

        foreach ($sensitive as $key) {
            $provided = array_key_exists($key, $incoming)
                && $incoming[$key] !== null
                && $incoming[$key] !== '';

            if ($provided) {
                $result[$key] = $incoming[$key];
            } elseif (array_key_exists($key, $existing)) {
                $result[$key] = $existing[$key]; // keep existing secret
            } else {
                unset($result[$key]); // no value anywhere: drop any empty echo
            }
        }

        return $result;
    }
}
