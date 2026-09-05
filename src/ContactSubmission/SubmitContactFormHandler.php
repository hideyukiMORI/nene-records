<?php

declare(strict_types=1);

namespace NeNeRecords\ContactSubmission;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\ClockInterface;
use Nene2\Middleware\RateLimitStorageInterface;
use NeNeRecords\Http\ClientIp;
use NeNeRecords\PublicRecord\ContactFormSchema;
use NeNeRecords\PublicRecord\ContactFormSchemaProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Receives the public contact form and hands it to the product that owns the form (#1031).
 *
 * This is the one endpoint in records that anyone on the internet can post to without
 * credentials, so the guards are the feature, not decoration:
 *
 * 1. **origin** — the post must come from a page on this host (`Origin`, falling back to
 *    `Referer`). Neither present means refuse: a cookie-less public POST gets no CSRF
 *    protection anywhere else in the stack.
 * 2. **throttle** — two buckets, per visitor IP and per form. One alone is not enough: a
 *    single IP flooding one form is caught by the first, a botnet spread across addresses is
 *    caught by the second.
 * 3. **caps** — field count, value length, total bytes. Rate limiting alone lets a small
 *    number of enormous requests through.
 * 4. **schema conformance** — only keys the *upstream schema* declares are forwarded. Deriving
 *    the allow-list from the schema means records never holds a second opinion about what is
 *    forwardable, so nothing can be added here by accident.
 * 5. **visible failure** — every refusal and every upstream error is logged with enough to act
 *    on and nothing the visitor typed.
 *
 * Responses: a submission that passes the guards ends in a 303 back to the page it came from,
 * so a reload does not resubmit. Guard failures answer with their real status (422/413/429) —
 * they are not a normal visitor outcome, and a machine sending malformed payloads should be
 * told plainly.
 */
final readonly class SubmitContactFormHandler
{
    /** Field the SSR form renders hidden; a human never fills it, a naive bot does. */
    public const HONEYPOT_FIELD = 'website_url';

    private const MAX_FIELDS = 40;
    private const MAX_VALUE_LENGTH = 5000;
    private const MAX_TOTAL_BYTES = 65536;

    private const CONTROL_FIELDS = ['form_key', 'return_path', 'consent', self::HONEYPOT_FIELD];

    public function __construct(
        private ContactFormSchemaProviderInterface $schemas,
        private ContactSubmissionSenderInterface $sender,
        private RateLimitStorageInterface $rateLimit,
        private ProblemDetailsResponseFactory $problemDetails,
        private ResponseFactoryInterface $responses,
        private ClockInterface $clock,
        private LoggerInterface $logger,
        private int $perIpLimit = 10,
        private int $perFormLimit = 300,
        private int $windowSeconds = 3600,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $body = is_array($body) ? $body : [];
        $returnPath = ReturnPath::sanitize($body['return_path'] ?? null);

        $originFailure = $this->rejectForeignOrigin($request);

        if ($originFailure !== null) {
            return $originFailure;
        }

        $formKey = $body['form_key'] ?? null;

        if (!is_string($formKey) || $formKey === '' || preg_match('/^[A-Za-z0-9_-]{1,64}$/', $formKey) !== 1) {
            return $this->refuse($request, 422, 'A valid form key is required.', 'form-key-missing', null);
        }

        $sizeFailure = $this->rejectOversizedBody($request, $body, $formKey);

        if ($sizeFailure !== null) {
            return $sizeFailure;
        }

        $throttled = $this->throttle($request, $formKey);

        if ($throttled !== null) {
            return $throttled;
        }

        $schema = $this->schemas->schemaFor($formKey);

        if ($schema === null) {
            // Same answer as an unknown form: the endpoint must not become a way to probe which
            // form keys exist.
            return $this->refuse($request, 422, 'This form cannot accept submissions.', 'schema-unavailable', $formKey);
        }

        // Honeypot: answer exactly as if it had worked. Telling a bot it was caught only teaches
        // it to stop filling the field. The drop is still logged — a silent drop would make
        // "my message never arrived" unanswerable.
        if (trim((string) ($body[self::HONEYPOT_FIELD] ?? '')) !== '') {
            $this->logger->info('contact-submission: dropped by honeypot', [
                'form_key' => $formKey,
                'ip' => ClientIp::resolve($request),
            ]);

            return $this->redirect(ReturnPath::withOutcome($returnPath, 'ok'));
        }

        $values = $this->collectDeclaredValues($body, $schema);

        if ($values === null) {
            return $this->refuse($request, 422, 'The submission does not match this form.', 'schema-mismatch', $formKey);
        }

        $missing = $this->missingRequiredField($values, $schema);

        if ($missing !== null) {
            return $this->refuse($request, 422, 'A required field is missing.', 'required-missing:' . $missing, $formKey);
        }

        $consent = trim((string) ($body['consent'] ?? '')) !== '';

        if ($schema->consentRequired && !$consent) {
            return $this->refuse($request, 422, 'Consent is required.', 'consent-missing', $formKey);
        }

        $result = $this->sender->send($formKey, $values, $consent);

        if (!$result->delivered) {
            // The visitor gets a generic failure; the operator gets the reason. Losing an
            // enquiry silently is the one outcome this endpoint must never have.
            $this->logger->error('contact-submission: upstream delivery failed', [
                'form_key' => $formKey,
                'reason' => $result->reason,
                'upstream_status' => $result->upstreamStatus,
            ]);

            return $this->redirect(ReturnPath::withOutcome($returnPath, 'error'));
        }

        return $this->redirect(ReturnPath::withOutcome($returnPath, 'ok'));
    }

    /**
     * The form is served by this host, so a post from anywhere else is not a visitor using it.
     */
    private function rejectForeignOrigin(ServerRequestInterface $request): ?ResponseInterface
    {
        $expected = strtolower($request->getUri()->getHost());
        $origin = $request->getHeaderLine('Origin');
        $referer = $request->getHeaderLine('Referer');
        $claimed = $origin !== '' ? $origin : $referer;

        if ($claimed === '') {
            // Fail-closed. Browsers send `Origin` on form POSTs; a request with neither header
            // is not a page on this site submitting a form.
            return $this->refuse($request, 403, 'This form must be submitted from the site it appears on.', 'origin-absent', null);
        }

        $host = parse_url($claimed, PHP_URL_HOST);

        if (!is_string($host) || strtolower($host) !== $expected) {
            return $this->refuse($request, 403, 'This form must be submitted from the site it appears on.', 'origin-mismatch', null);
        }

        return null;
    }

    /**
     * @param array<array-key, mixed> $body
     */
    private function rejectOversizedBody(ServerRequestInterface $request, array $body, string $formKey): ?ResponseInterface
    {
        if (count($body) > self::MAX_FIELDS) {
            return $this->refuse($request, 422, 'Too many fields.', 'too-many-fields', $formKey);
        }

        $total = 0;

        foreach ($body as $key => $value) {
            if (!is_string($value)) {
                // Arrays (`name[]=…`) are not part of any schema records renders.
                return $this->refuse($request, 422, 'Unexpected field shape.', 'non-scalar-field', $formKey);
            }

            if (strlen($value) > self::MAX_VALUE_LENGTH) {
                return $this->refuse($request, 422, 'A field is too long.', 'field-too-long', $formKey);
            }

            $total += strlen((string) $key) + strlen($value);
        }

        if ($total > self::MAX_TOTAL_BYTES) {
            return $this->refuse($request, 413, 'The submission is too large.', 'body-too-large', $formKey);
        }

        return null;
    }

    private function throttle(ServerRequestInterface $request, string $formKey): ?ResponseInterface
    {
        // ClientIp, not REMOTE_ADDR: behind the single trusted proxy the latter is the proxy's
        // address, which would put every visitor in one bucket (#1036).
        $ip = ClientIp::resolve($request);

        $perIp = $this->rateLimit->hit('contact:ip:' . $ip, $this->windowSeconds);

        if ($perIp['count'] > $this->perIpLimit) {
            return $this->tooManyRequests($request, $perIp['reset_at'], $this->perIpLimit, $formKey, 'per-ip');
        }

        $perForm = $this->rateLimit->hit('contact:form:' . $formKey, $this->windowSeconds);

        if ($perForm['count'] > $this->perFormLimit) {
            // Catches what the per-IP bucket cannot: many addresses hitting one form.
            return $this->tooManyRequests($request, $perForm['reset_at'], $this->perFormLimit, $formKey, 'per-form');
        }

        return null;
    }

    private function tooManyRequests(
        ServerRequestInterface $request,
        int $resetAt,
        int $limit,
        string $formKey,
        string $bucket,
    ): ResponseInterface {
        $retryAfter = max(0, $resetAt - $this->clock->now()->getTimestamp());

        $this->logger->warning('contact-submission: throttled', [
            'form_key' => $formKey,
            'bucket' => $bucket,
            'ip' => ClientIp::resolve($request),
        ]);

        return $this->problemDetails->create(
            $request,
            'too-many-requests',
            'Too Many Requests',
            429,
            sprintf('Rate limit of %d per %d seconds exceeded.', $limit, $this->windowSeconds),
        )
            ->withHeader('Retry-After', (string) $retryAfter)
            ->withHeader('X-RateLimit-Limit', (string) $limit)
            ->withHeader('X-RateLimit-Remaining', '0')
            ->withHeader('X-RateLimit-Reset', (string) $resetAt);
    }

    /**
     * Keeps exactly the keys the schema declares. Any other key means the payload was not
     * produced by the form records rendered, so the whole submission is refused rather than
     * quietly trimmed — trimming would let a caller probe which keys survive.
     *
     * @param array<array-key, mixed> $body
     *
     * @return array<string, string>|null null when the body carries a key the schema does not
     */
    private function collectDeclaredValues(array $body, ContactFormSchema $schema): ?array
    {
        $declared = [];

        foreach ($schema->fields as $field) {
            // Declared but never rendered (#1066), so it is accepted like a control field and
            // dropped rather than forwarded: the issuing product discards honeypot values on
            // ingest anyway, and refusing the whole submission would punish a browser that still
            // holds a page rendered before the fix.
            $declared[$field->key] = $field->isHoneypot() ? 'drop' : true;
        }

        $values = [];

        foreach ($body as $key => $value) {
            $key = (string) $key;

            if (in_array($key, self::CONTROL_FIELDS, true)) {
                continue;
            }

            if (!isset($declared[$key])) {
                return null;
            }

            if ($declared[$key] === 'drop') {
                continue;
            }

            $values[$key] = is_string($value) ? $value : '';
        }

        return $values;
    }

    /**
     * @param array<string, string> $values
     */
    private function missingRequiredField(array $values, ContactFormSchema $schema): ?string
    {
        foreach ($schema->fields as $field) {
            if ($field->required && trim($values[$field->key] ?? '') === '') {
                return $field->key;
            }
        }

        return null;
    }

    private function refuse(
        ServerRequestInterface $request,
        int $status,
        string $detail,
        string $reason,
        ?string $formKey,
    ): ResponseInterface {
        // The visitor-facing detail stays generic; the reason goes to the log. An abuser must
        // not be able to use the response to learn which guard rejected them.
        $this->logger->warning('contact-submission: refused', [
            'form_key' => $formKey,
            'reason' => $reason,
            'status' => $status,
            'ip' => ClientIp::resolve($request),
        ]);

        return $this->problemDetails->create($request, 'contact-submission-refused', 'Unprocessable', $status, $detail);
    }

    private function redirect(string $location): ResponseInterface
    {
        // 303 so the browser re-issues as GET: a reload must not resubmit the form.
        return $this->responses->createResponse(303)->withHeader('Location', $location);
    }
}
