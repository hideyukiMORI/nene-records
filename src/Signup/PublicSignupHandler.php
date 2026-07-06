<?php

declare(strict_types=1);

namespace NeNeRecords\Signup;

use DateTimeImmutable;
use DateTimeInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\ClockInterface;
use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Middleware\RateLimitStorageInterface;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeNeRecords\Auth\SessionCookie;
use NeNeRecords\Http\ClientIp;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * `POST /api/v1/public/signup` — public self-serve tenant registration. Validates
 * the requested slug / admin credentials, provisions the tenant, and returns the
 * session (HttpOnly cookie) so the new admin is immediately signed in.
 *
 * Always-open (AdminApiAuthMiddleware `/api/v1/public/`) and org-resolution
 * bypassed (it creates a *new* org and carries no tenant context of its own).
 */
final readonly class PublicSignupHandler
{
    private const PASSWORD_MIN = 8;

    public function __construct(
        private PublicSignupUseCaseInterface $useCase,
        private JsonResponseFactory $response,
        private ProblemDetailsResponseFactory $problemDetails,
        private RateLimitStorageInterface $rateLimitStorage,
        private ClockInterface $clock,
        /** Max tenant signups accepted per client IP within the window. */
        private int $maxSignupsPerWindow = 5,
        /** Rate-limit window in seconds (default: 1 hour). */
        private int $rateLimitWindowSeconds = 3600,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);

        $name = $this->string($body, 'organization_name');
        $slug = strtolower($this->string($body, 'slug'));
        $email = $this->string($body, 'email');
        $password = isset($body['password']) && is_string($body['password']) ? $body['password'] : '';

        $errors = [];

        if ($name === '') {
            $errors[] = new ValidationError('organization_name', 'Organization name is required.', 'required');
        } elseif (mb_strlen($name) > 100) {
            $errors[] = new ValidationError('organization_name', 'Organization name is too long.', 'max_length');
        }

        if ($slug === '') {
            $errors[] = new ValidationError('slug', 'Slug is required.', 'required');
        } elseif (!ReservedSlugs::isValidFormat($slug)) {
            $errors[] = new ValidationError('slug', 'Slug must be 3–30 lowercase letters, numbers or hyphens.', 'format');
        } elseif (ReservedSlugs::isReserved($slug)) {
            $errors[] = new ValidationError('slug', 'This slug is reserved. Please choose another.', 'reserved');
        }

        if ($email === '') {
            $errors[] = new ValidationError('email', 'Email is required.', 'required');
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = new ValidationError('email', 'Email is invalid.', 'format');
        }

        if ($password === '') {
            $errors[] = new ValidationError('password', 'Password is required.', 'required');
        } elseif (mb_strlen($password) < self::PASSWORD_MIN) {
            $errors[] = new ValidationError('password', 'Password must be at least 8 characters.', 'min_length');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        // Abuse guard: throttle well-formed signups per client IP so a script
        // can't mass-create tenants. Checked after field validation (a mistyped
        // form must not burn the budget) and before the expensive provisioning.
        $rateLimited = $this->enforceRateLimit($request);
        if ($rateLimited !== null) {
            return $rateLimited;
        }

        // The verification link points back at the host the signup came from
        // (the apex), where /verify-email confirms via the global token endpoint.
        $uri = $request->getUri();
        $verifyUrlBase = $uri->getScheme() . '://' . $uri->getAuthority();

        // Slug / email uniqueness raise domain exceptions (→ 409) past this point.
        $output = $this->useCase->execute(new PublicSignupInput(
            organizationName: $name,
            slug: $slug,
            email: $email,
            password: $password,
            verifyUrlBase: $verifyUrlBase,
        ));

        $cookie = SessionCookie::build(
            $output->token,
            $output->expiresAt - $this->clock->now()->getTimestamp(),
            SessionCookie::isSecureRequest($request),
        );

        return $this->response->create([
            'token'      => $output->token,
            'expires_at' => (new DateTimeImmutable('@' . $output->expiresAt))->format(DateTimeInterface::ATOM),
            'slug'       => $output->slug,
            'org_id'     => $output->organizationId,
            'email'      => $output->email,
            'role'       => $output->role,
        ], 201, ['Set-Cookie' => $cookie]);
    }

    /**
     * Throttle well-formed signups per client IP. Returns a 429 Problem Details
     * response when the limit is exceeded, or null when the request may proceed.
     */
    private function enforceRateLimit(ServerRequestInterface $request): ?ResponseInterface
    {
        $key = 'signup:' . ClientIp::resolve($request);
        $result = $this->rateLimitStorage->hit($key, $this->rateLimitWindowSeconds);

        if ($result['count'] <= $this->maxSignupsPerWindow) {
            return null;
        }

        $retryAfter = max(0, $result['reset_at'] - $this->clock->now()->getTimestamp());

        return $this->problemDetails->create(
            $request,
            'too-many-requests',
            'Too Many Requests',
            429,
            sprintf(
                'Signup rate limit of %d per %d seconds exceeded. Try again in %d seconds.',
                $this->maxSignupsPerWindow,
                $this->rateLimitWindowSeconds,
                $retryAfter,
            ),
        )
            ->withHeader('Retry-After', (string) $retryAfter)
            ->withHeader('X-RateLimit-Limit', (string) $this->maxSignupsPerWindow)
            ->withHeader('X-RateLimit-Remaining', '0')
            ->withHeader('X-RateLimit-Reset', (string) $result['reset_at']);
    }

    /** @param array<string, mixed> $body */
    private function string(array $body, string $key): string
    {
        return isset($body[$key]) && is_string($body[$key]) ? trim($body[$key]) : '';
    }
}
