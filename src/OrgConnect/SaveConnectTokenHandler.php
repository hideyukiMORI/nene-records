<?php

declare(strict_types=1);

namespace NeNeRecords\OrgConnect;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class SaveConnectTokenHandler
{
    /** Short enough to be a paste accident, and no real token is this small. */
    private const MIN_LENGTH = 16;

    /** Generous for a signed JWT; the point is to bound what reaches the cipher. */
    private const MAX_LENGTH = 4096;

    /**
     * base64url plus the separators real bearer tokens use. Whitespace and control
     * characters are excluded on purpose: a token that "works" with a stray newline in
     * it would fail later, at the far end of a server-to-server call, where the error is
     * much harder to read.
     */
    private const ALLOWED_PATTERN = '/^[A-Za-z0-9._~+\/=-]+$/';

    public function __construct(
        private SaveConnectTokenUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $service = ConnectTokenServiceResolver::fromPath($request);
        $body = JsonRequestBodyParser::parse($request);
        $raw = $body['token'] ?? null;
        $token = is_string($raw) ? trim($raw) : '';

        if ($token === '') {
            throw new ValidationException([
                new ValidationError('token', 'Token is required.', 'required'),
            ]);
        }

        $errors = $this->validate($token);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $output = $this->useCase->execute(new SaveConnectTokenInput($service, $token));
        $payload = ConnectTokenHttpMapper::toArray($output->summary);

        if (!$output->created) {
            return $this->response->create($payload);
        }

        return $this->response->create($payload, 201)
            ->withHeader('Location', '/api/v1/connect-tokens/' . $service->value);
    }

    /**
     * @param non-empty-string $token
     *
     * @return list<ValidationError>
     */
    private function validate(string $token): array
    {
        $errors = [];
        $length = strlen($token);

        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            $errors[] = new ValidationError(
                'token',
                'Token must be between ' . self::MIN_LENGTH . ' and ' . self::MAX_LENGTH . ' characters.',
                'invalid',
            );
        }

        if (preg_match(self::ALLOWED_PATTERN, $token) !== 1) {
            $errors[] = new ValidationError(
                'token',
                'Token contains characters that are not valid in a bearer token.',
                'invalid',
            );
        }

        return $errors;
    }
}
