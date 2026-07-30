<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use NeNeRecords\Http\SsrfGuard;
use Throwable;

/**
 * Fetches a contact form's public schema from the issuing product over HTTP.
 *
 * The endpoint is unauthenticated by design (`GET {base}/public/forms/{key}/schema`) — the
 * form key is a handle, not a secret, so **no connect-token is sent here**. The token belongs
 * to the submission path (#1031) and nowhere else; keeping the read path credential-free
 * means a schema fetch can never be the thing that leaks it.
 *
 * The base URL comes from the operator (`NENE_RECORDS_CONTACT_BASE_URL`), not from tenant
 * data, but it is still SSRF-guarded and connection-pinned: a misconfigured deployment
 * should fail rather than let a public page drive requests at internal addresses.
 *
 * Every failure returns null, which the renderer turns into a visible notice. This is called
 * while rendering a public page, so the timeout is deliberately short — a slow sibling must
 * not hold a page open.
 */
final readonly class HttpContactFormSchemaProvider implements ContactFormSchemaProviderInterface
{
    private const TIMEOUT_SECONDS = 3;

    /** Bounds the response we are willing to parse; a real schema is a few kilobytes. */
    private const MAX_BODY_BYTES = 262144;

    public function __construct(
        private ?string $baseUrl,
        private SsrfGuard $ssrfGuard = new SsrfGuard(),
    ) {
    }

    public function schemaFor(string $formKey): ?ContactFormSchema
    {
        $base = $this->baseUrl === null ? '' : rtrim(trim($this->baseUrl), '/');

        // Not configured is a normal state (most tenants never place a contact block), and it
        // must not throw out of a page render.
        if ($base === '' || preg_match('/^[A-Za-z0-9_-]+$/', $formKey) !== 1) {
            return null;
        }

        try {
            $body = $this->get($base . '/public/forms/' . $formKey . '/schema');
        } catch (Throwable) {
            return null;
        }

        if ($body === null) {
            return null;
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $this->toSchema($formKey, $decoded) : null;
    }

    private function get(string $url): ?string
    {
        $inspection = $this->ssrfGuard->inspect($url);

        if (!$inspection->allowed) {
            return null;
        }

        $handle = curl_init($url);

        if ($handle === false) {
            return null;
        }

        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: NeNeRecords-ContactEmbed/1.0'],
            CURLOPT_RESOLVE => $this->pinnedAddresses($url, $inspection->addresses),
        ]);

        $result = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);

        if (!is_string($result) || $status < 200 || $status >= 300) {
            return null;
        }

        return strlen($result) > self::MAX_BODY_BYTES ? null : $result;
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    private function toSchema(string $formKey, array $payload): ?ContactFormSchema
    {
        $rawFields = $payload['fields'] ?? null;

        if (!is_array($rawFields)) {
            return null;
        }

        $fields = [];

        foreach ($rawFields as $rawField) {
            if (!is_array($rawField)) {
                continue;
            }

            $key = $rawField['key'] ?? null;

            if (!is_string($key) || $key === '') {
                continue;
            }

            $label = $rawField['label'] ?? null;
            $type = $rawField['type'] ?? null;

            $fields[] = new ContactFormField(
                key: $key,
                label: is_string($label) && $label !== '' ? $label : $key,
                type: is_string($type) ? $type : 'text',
                required: (bool) ($rawField['required'] ?? false),
                options: $this->stringList($rawField['options'] ?? null),
            );
        }

        $submitLabel = $payload['submitLabel'] ?? null;
        $consentLabel = $payload['consentLabel'] ?? null;

        return new ContactFormSchema(
            formKey: $formKey,
            fields: $fields,
            consentRequired: (bool) ($payload['consentRequired'] ?? false),
            submitLabel: is_string($submitLabel) && $submitLabel !== '' ? $submitLabel : null,
            consentLabel: is_string($consentLabel) && $consentLabel !== '' ? $consentLabel : null,
        );
    }

    /** @return list<string> */
    private function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $strings = [];

        foreach ($value as $item) {
            if (is_string($item)) {
                $strings[] = $item;
            }
        }

        return $strings;
    }

    /**
     * @param list<string> $addresses
     *
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
