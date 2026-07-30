<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\OrgConnect;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use NeNeRecords\Config\AesGcmConfigCipher;
use NeNeRecords\OrgConnect\ConnectTokenNotFoundExceptionHandler;
use NeNeRecords\OrgConnect\ConnectTokenRouteRegistrar;
use NeNeRecords\OrgConnect\ConnectTokenService;
use NeNeRecords\OrgConnect\DeleteConnectTokenHandler;
use NeNeRecords\OrgConnect\DeleteConnectTokenUseCase;
use NeNeRecords\OrgConnect\ListConnectTokensHandler;
use NeNeRecords\OrgConnect\ListConnectTokensUseCase;
use NeNeRecords\OrgConnect\SaveConnectTokenHandler;
use NeNeRecords\OrgConnect\SaveConnectTokenUseCase;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ConnectTokenHttpTest extends TestCase
{
    private const TOKEN = 'eyJhbGciOiJIUzI1NiJ9.payload.signature-abcd';

    private Psr17Factory $factory;
    private InMemoryConnectTokenRepository $tokens;
    private RequestHandlerInterface $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();
        $this->tokens = new InMemoryConnectTokenRepository();

        $jsonResponse = new JsonResponseFactory($this->factory, $this->factory);
        $problemDetails = new ProblemDetailsResponseFactory($this->factory, $this->factory);
        $cipher = new AesGcmConfigCipher(new TestConfigKeyResolver());

        $registrar = new ConnectTokenRouteRegistrar(
            new ListConnectTokensHandler(new ListConnectTokensUseCase($this->tokens), $jsonResponse),
            new SaveConnectTokenHandler(new SaveConnectTokenUseCase($this->tokens, $cipher), $jsonResponse),
            new DeleteConnectTokenHandler(new DeleteConnectTokenUseCase($this->tokens), $jsonResponse),
        );

        $this->application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [
                new ConnectTokenNotFoundExceptionHandler($problemDetails),
            ],
            routeRegistrars: [$registrar],
        ))->create();
    }

    public function testListStartsEmpty(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/connect-tokens'),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([], $this->decodeJson($response)['items']);
    }

    public function testInstallingATokenReturns201WithoutEchoingIt(): void
    {
        $response = $this->put(self::TOKEN);
        $payload = $this->decodeJson($response);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame('/api/v1/connect-tokens/contact', $response->getHeaderLine('Location'));
        self::assertSame('contact', $payload['service']);
        self::assertSame('abcd', $payload['token_hint']);
        self::assertStringNotContainsString(self::TOKEN, (string) $response->getBody());
    }

    public function testReplacingATokenReturns200(): void
    {
        $this->put(self::TOKEN);
        $response = $this->put('second-token-value-wxyz');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('wxyz', $this->decodeJson($response)['token_hint']);
    }

    public function testListShowsTheHintAndNeverTheToken(): void
    {
        $this->put(self::TOKEN);

        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/connect-tokens'),
        );
        $body = (string) $response->getBody();

        $items = $this->decodeJson($response)['items'];
        self::assertIsArray($items);
        $first = $items[0];
        self::assertIsArray($first);

        self::assertSame('abcd', $first['token_hint']);
        self::assertStringNotContainsString(self::TOKEN, $body);
        self::assertArrayNotHasKey('token', $first);
        self::assertArrayNotHasKey('token_ciphertext', $first);
    }

    public function testUnknownServiceIs404(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('PUT', 'https://example.test/api/v1/connect-tokens/invoice')
                ->withBody($this->factory->createStream((string) json_encode(['token' => self::TOKEN]))),
        );

        self::assertSame(404, $response->getStatusCode());
    }

    public function testMissingTokenIs422(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('PUT', 'https://example.test/api/v1/connect-tokens/contact')
                ->withBody($this->factory->createStream((string) json_encode(['token' => '   ']))),
        );

        self::assertSame(422, $response->getStatusCode());
    }

    public function testTokenWithWhitespaceOrControlCharactersIsRejected(): void
    {
        // A pasted token that wrapped across lines would otherwise be stored and only fail
        // much later, at the far end of a server-to-server call.
        $response = $this->put("eyJhbGciOiJIUzI1NiJ9.pay\nload.signature");

        self::assertSame(422, $response->getStatusCode());
    }

    public function testShortTokenIsRejected(): void
    {
        self::assertSame(422, $this->put('too-short')->getStatusCode());
    }

    public function testDeletingRemovesItAndIsIdempotentOnlyOnce(): void
    {
        $this->put(self::TOKEN);

        $deleted = $this->application->handle(
            $this->factory->createServerRequest('DELETE', 'https://example.test/api/v1/connect-tokens/contact'),
        );

        self::assertSame(204, $deleted->getStatusCode());
        self::assertNull($this->tokens->storedEnvelope(ConnectTokenService::Contact));

        $again = $this->application->handle(
            $this->factory->createServerRequest('DELETE', 'https://example.test/api/v1/connect-tokens/contact'),
        );

        self::assertSame(404, $again->getStatusCode());
    }

    private function put(string $token): ResponseInterface
    {
        return $this->application->handle(
            $this->factory->createServerRequest('PUT', 'https://example.test/api/v1/connect-tokens/contact')
                ->withBody($this->factory->createStream((string) json_encode(['token' => $token]))),
        );
    }

    /** @return array<string, mixed> */
    private function decodeJson(ResponseInterface $response): array
    {
        $decoded = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($decoded);

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }
}
