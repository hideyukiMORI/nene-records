<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

use NeNeRecords\Http\SsrfGuard;
use Throwable;

/**
 * Delivers a webhook payload over HTTP via cURL (5 s timeout).
 *
 * The destination URL is supplied by an org admin, who is not infra-trusted in a
 * multi-tenant deployment, so every send is SSRF-guarded ({@see SsrfGuard}):
 *   - only http/https destinations are allowed;
 *   - the host is resolved and every resolved address must be publicly routable —
 *     private, loopback, link-local (incl. the metadata endpoint 169.254.169.254),
 *     CGN and reserved ranges are refused before any connection;
 *   - the connection is pinned to the verified IP(s) via CURLOPT_RESOLVE and
 *     redirects are disabled, so DNS rebinding cannot bounce the request to an
 *     internal address after the check.
 *
 * A 2xx response is a success; any other status, a rejected URL, a transport
 * error, or an exception is a failure that the {@see WebhookDeliveryProcessor}
 * can retry.
 */
final readonly class CurlWebhookSender implements WebhookSenderInterface
{
    private const TIMEOUT_SECONDS = 5;

    public function __construct(
        private SsrfGuard $ssrfGuard = new SsrfGuard(),
    ) {
    }

    public function send(string $url, ?string $secret, string $payload): WebhookSendResult
    {
        try {
            $inspection = $this->ssrfGuard->inspect($url);
            if (!$inspection->allowed) {
                return WebhookSendResult::failure('Webhook URL rejected: ' . ($inspection->reason ?? 'not allowed.'));
            }

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
                CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_RESOLVE => $this->pinnedAddresses($url, $inspection->addresses),
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

    /**
     * Pin the host to the addresses already verified public so cURL connects to
     * them instead of re-resolving (DNS-rebinding defence).
     *
     * @param list<string> $addresses
     * @return list<string> CURLOPT_RESOLVE entries "host:port:ip"
     */
    private function pinnedAddresses(string $url, array $addresses): array
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return [];
        }
        $host = trim($host, '[]');

        $port = parse_url($url, PHP_URL_PORT);
        if (!is_int($port)) {
            $scheme = parse_url($url, PHP_URL_SCHEME);
            $port = is_string($scheme) && strtolower($scheme) === 'https' ? 443 : 80;
        }

        $entries = [];
        foreach ($addresses as $ip) {
            $entries[] = $host . ':' . $port . ':' . $ip;
        }

        return $entries;
    }
}
