<?php

declare(strict_types=1);

namespace NeNeRecords\ContactSubmission;

use NeNeRecords\Config\ConfigDecryptException;
use NeNeRecords\Config\ConfigKeyException;
use NeNeRecords\Http\SsrfGuard;
use NeNeRecords\OrgConnect\ConnectTokenProviderInterface;
use NeNeRecords\OrgConnect\ConnectTokenService;
use Throwable;

/**
 * Hands a validated submission to contact's ingest endpoint, server to server.
 *
 * This is the only place the connect-token leaves storage. It goes into an `Authorization`
 * header on an outbound request and nowhere else — never into a response, never into a log
 * line, never into the page.
 *
 * The form is addressed by **`public_form_key`**, not by contact's internal id (contact ruling
 * 2026-07-30, option B). records never learns the id, and contact's public schema endpoint
 * keeps its documented invariant of exposing no internal ids. Sending both keys at once is a
 * 422 upstream by design, so exactly one is sent.
 */
final readonly class HttpContactSubmissionSender implements ContactSubmissionSenderInterface
{
    private const INGEST_PATH = '/api/submissions';

    /** Longer than the schema read (3s): this one must not give up on a real enquiry too eagerly. */
    private const TIMEOUT_SECONDS = 8;

    public function __construct(
        private ?string $baseUrl,
        private ConnectTokenProviderInterface $tokens,
        private SsrfGuard $ssrfGuard = new SsrfGuard(),
    ) {
    }

    public function send(string $formKey, array $values, bool $consent): ContactSubmissionSendResult
    {
        $base = $this->baseUrl === null ? '' : rtrim(trim($this->baseUrl), '/');

        if ($base === '') {
            return ContactSubmissionSendResult::failed('NENE_RECORDS_CONTACT_BASE_URL is not configured.');
        }

        try {
            $token = $this->tokens->secretFor(ConnectTokenService::Contact);
        } catch (ConfigDecryptException $e) {
            // Distinct from "absent" on purpose (#1029): the operator re-pastes the token rather
            // than installing one for the first time.
            return ContactSubmissionSendResult::failed('Connect token could not be decrypted (rotated key?): ' . $e->getMessage());
        } catch (ConfigKeyException $e) {
            return ContactSubmissionSendResult::failed('Config key unusable: ' . $e->getMessage());
        }

        if ($token === null) {
            return ContactSubmissionSendResult::failed('No connect token is installed for this organization.');
        }

        $payload = json_encode(ContactIngestPayload::build($formKey, $values, $consent), JSON_THROW_ON_ERROR);

        try {
            return $this->post($base . self::INGEST_PATH, $payload, $token);
        } catch (Throwable $e) {
            // A visitor waiting on a page must not see a stack trace, and the enquiry must not
            // vanish quietly — the handler logs this reason.
            return ContactSubmissionSendResult::failed('Transport error: ' . $e->getMessage());
        }
    }

    private function post(string $url, string $payload, string $token): ContactSubmissionSendResult
    {
        $inspection = $this->ssrfGuard->inspect($url);

        if (!$inspection->allowed) {
            return ContactSubmissionSendResult::failed('Upstream URL rejected: ' . ($inspection->reason ?? 'not allowed.'));
        }

        $handle = curl_init($url);

        if ($handle === false) {
            return ContactSubmissionSendResult::failed('Failed to initialize cURL.');
        }

        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
                'User-Agent: NeNeRecords-ContactEmbed/1.0',
            ],
            CURLOPT_RESOLVE => $this->pinnedAddresses($url, $inspection->addresses),
        ]);

        $result = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if ($result === false || $status === 0) {
            return ContactSubmissionSendResult::failed($error !== '' ? $error : 'Transport error.');
        }

        if ($status >= 200 && $status < 300) {
            return ContactSubmissionSendResult::delivered();
        }

        // The upstream body may echo the submission, so it is not carried into the reason —
        // the status is what an operator acts on.
        return ContactSubmissionSendResult::failed('Upstream rejected the submission.', $status);
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
