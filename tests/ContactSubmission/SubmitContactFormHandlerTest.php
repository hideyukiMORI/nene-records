<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\ContactSubmission;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Middleware\RateLimitStorageInterface;
use NeNeRecords\ContactSubmission\ContactSubmissionSenderInterface;
use NeNeRecords\ContactSubmission\ContactSubmissionSendResult;
use NeNeRecords\ContactSubmission\SubmitContactFormHandler;
use NeNeRecords\PublicRecord\ContactFormField;
use NeNeRecords\PublicRecord\ContactFormSchema;
use NeNeRecords\PublicRecord\ContactFormSchemaProviderInterface;
use NeNeRecords\PublicRecord\ContactSubmissionProxyRoute;
use NeNeRecords\Tests\Support\FixedClock;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\AbstractLogger;

/**
 * The guards on the one endpoint anyone on the internet can post to without credentials.
 *
 * Every rejection test also asserts the submission **never reached the sender**: a guard that
 * returns the right status while still forwarding the payload would pass a status-only check
 * and be useless.
 */
final class SubmitContactFormHandlerTest extends TestCase
{
    private const HOST = 'example.test';

    private Psr17Factory $factory;
    private RecordingSender $sender;
    private InMemoryRateLimitStorage $rateLimit;
    private RecordingLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();
        $this->sender = new RecordingSender();
        $this->rateLimit = new InMemoryRateLimitStorage();
        $this->logger = new RecordingLogger();
    }

    public function testAcceptedSubmissionRedirectsBackToThePage(): void
    {
        $response = $this->post(['form_key' => 'k', 'email' => 'a@example.test', 'return_path' => '/article/hello']);

        // 303 so a reload does not resubmit.
        self::assertSame(303, $response->getStatusCode());
        self::assertSame('/article/hello?contact=ok', $response->getHeaderLine('Location'));
        self::assertCount(1, $this->sender->sent);
    }

    public function testUpstreamFailureIsVisibleToOperatorsAndToTheVisitor(): void
    {
        $this->sender->result = ContactSubmissionSendResult::failed('upstream 503', 503);

        $response = $this->post(['form_key' => 'k', 'email' => 'a@example.test', 'return_path' => '/article/hello']);

        self::assertSame('/article/hello?contact=error', $response->getHeaderLine('Location'));
        self::assertTrue($this->logger->has('error', 'upstream delivery failed'), 'A lost enquiry must be visible.');
    }

    // ── guard 1: origin ───────────────────────────────────────────────────────

    public function testPostWithoutOriginOrRefererIsRefused(): void
    {
        $request = $this->request(['form_key' => 'k', 'email' => 'a@example.test'], origin: null, referer: null);

        $response = (new SubmitContactFormHandlerFactory($this))->build()->handle($request);

        self::assertSame(403, $response->getStatusCode());
        self::assertSame([], $this->sender->sent);
    }

    public function testPostFromAnotherSiteIsRefused(): void
    {
        $request = $this->request(['form_key' => 'k', 'email' => 'a@example.test'], origin: 'https://evil.example');

        $response = (new SubmitContactFormHandlerFactory($this))->build()->handle($request);

        self::assertSame(403, $response->getStatusCode());
        self::assertSame([], $this->sender->sent);
    }

    public function testRefererIsAcceptedWhenOriginIsAbsent(): void
    {
        $request = $this->request(
            ['form_key' => 'k', 'email' => 'a@example.test'],
            origin: null,
            referer: 'https://' . self::HOST . '/article/hello',
        );

        $response = (new SubmitContactFormHandlerFactory($this))->build()->handle($request);

        self::assertSame(303, $response->getStatusCode());
    }

    // ── guard 2: throttle ─────────────────────────────────────────────────────

    public function testPerIpBucketStopsOneAddressFlooding(): void
    {
        $handler = (new SubmitContactFormHandlerFactory($this))->build(perIpLimit: 2, perFormLimit: 1000);

        for ($i = 0; $i < 2; $i++) {
            self::assertSame(303, $handler->handle($this->request(['form_key' => 'k', 'email' => 'a@example.test']))->getStatusCode());
        }

        $blocked = $handler->handle($this->request(['form_key' => 'k', 'email' => 'a@example.test']));

        self::assertSame(429, $blocked->getStatusCode());
        self::assertNotSame('', $blocked->getHeaderLine('Retry-After'));
        self::assertCount(2, $this->sender->sent);
    }

    public function testPerFormBucketStopsManyAddressesFloodingOneForm(): void
    {
        // The per-IP bucket cannot see this: every request comes from a different address.
        $handler = (new SubmitContactFormHandlerFactory($this))->build(perIpLimit: 1000, perFormLimit: 2);

        for ($i = 0; $i < 2; $i++) {
            $request = $this->request(['form_key' => 'k', 'email' => 'a@example.test'], ip: '203.0.113.' . $i);
            self::assertSame(303, $handler->handle($request)->getStatusCode());
        }

        $blocked = $handler->handle($this->request(['form_key' => 'k', 'email' => 'a@example.test'], ip: '203.0.113.9'));

        self::assertSame(429, $blocked->getStatusCode());
        self::assertCount(2, $this->sender->sent);
    }

    public function testThrottleKeysOnTheForwardedClientIpNotTheProxy(): void
    {
        // #1036: REMOTE_ADDR is the proxy behind the single trusted ingress, so keying on it
        // would put every visitor in one bucket.
        $handler = (new SubmitContactFormHandlerFactory($this))->build(perIpLimit: 1);

        self::assertSame(303, $handler->handle($this->request(['form_key' => 'k', 'email' => 'a@example.test'], ip: '198.51.100.1'))->getStatusCode());
        self::assertSame(303, $handler->handle($this->request(['form_key' => 'k', 'email' => 'a@example.test'], ip: '198.51.100.2'))->getStatusCode());
    }

    // ── guard 3: caps ─────────────────────────────────────────────────────────

    public function testTooManyFieldsIsRefused(): void
    {
        $body = ['form_key' => 'k'];

        for ($i = 0; $i < 60; $i++) {
            $body['f' . $i] = 'x';
        }

        self::assertSame(422, $this->post($body)->getStatusCode());
        self::assertSame([], $this->sender->sent);
    }

    public function testOverlongValueIsRefused(): void
    {
        $response = $this->post(['form_key' => 'k', 'email' => str_repeat('a', 5001)]);

        self::assertSame(422, $response->getStatusCode());
        self::assertSame([], $this->sender->sent);
    }

    public function testOversizedBodyIsRefusedWith413(): void
    {
        $body = ['form_key' => 'k'];

        // Under the per-field cap, over the total cap — rate limiting alone would let this by.
        for ($i = 0; $i < 20; $i++) {
            $body['f' . $i] = str_repeat('a', 4900);
        }

        self::assertSame(413, $this->post($body)->getStatusCode());
        self::assertSame([], $this->sender->sent);
    }

    public function testArrayValuedFieldIsRefused(): void
    {
        self::assertSame(422, $this->post(['form_key' => 'k', 'email' => ['a', 'b']])->getStatusCode());
        self::assertSame([], $this->sender->sent);
    }

    // ── guard 4: schema conformance ───────────────────────────────────────────

    public function testFieldOutsideTheSchemaIsRefusedAndNothingIsForwarded(): void
    {
        $response = $this->post(['form_key' => 'k', 'email' => 'a@example.test', 'not_in_schema' => 'x']);

        self::assertSame(422, $response->getStatusCode());
        // The point of the guard: the payload must not reach the issuing product at all.
        self::assertSame([], $this->sender->sent);
    }

    public function testOnlyDeclaredKeysAreForwarded(): void
    {
        $this->post([
            'form_key' => 'k',
            'email' => 'a@example.test',
            'return_path' => '/x',
            'consent' => '1',
        ]);

        self::assertSame([['email' => 'a@example.test']], array_map(
            static fn (array $sent): array => $sent['values'],
            $this->sender->sent,
        ));
    }

    public function testMissingRequiredFieldIsRefused(): void
    {
        self::assertSame(422, $this->post(['form_key' => 'k', 'email' => '  '])->getStatusCode());
        self::assertSame([], $this->sender->sent);
    }

    public function testUnknownFormKeyIsRefused(): void
    {
        $handler = (new SubmitContactFormHandlerFactory($this))->build(withoutSchema: true);

        $response = $handler->handle($this->request(['form_key' => 'nope', 'email' => 'a@example.test']));

        self::assertSame(422, $response->getStatusCode());
        self::assertSame([], $this->sender->sent);
    }

    // ── guard 5: visible failure, and the honeypot ────────────────────────────

    public function testEveryRefusalIsLogged(): void
    {
        $this->post(['form_key' => 'k', 'email' => 'a@example.test', 'not_in_schema' => 'x']);

        self::assertTrue($this->logger->has('warning', 'refused'));
    }

    public function testRefusalDoesNotTellTheCallerWhichGuardFired(): void
    {
        $body = (string) $this->post(['form_key' => 'k', 'email' => 'a@example.test', 'not_in_schema' => 'x'])->getBody();

        // The reason belongs in the log, not in a response an abuser can iterate against.
        self::assertStringNotContainsString('schema-mismatch', $body);
        self::assertStringNotContainsString('not_in_schema', $body);
    }

    public function testHoneypotDropLooksLikeSuccessButIsRecorded(): void
    {
        $response = $this->post([
            'form_key' => 'k',
            'email' => 'a@example.test',
            SubmitContactFormHandler::HONEYPOT_FIELD => 'http://spam.example',
        ]);

        // Telling a bot it was caught only teaches it to stop filling the field.
        self::assertSame(303, $response->getStatusCode());
        self::assertStringEndsWith('contact=ok', $response->getHeaderLine('Location'));
        self::assertSame([], $this->sender->sent);
        self::assertTrue($this->logger->has('info', 'honeypot'), 'A silent drop makes "it never arrived" unanswerable.');
    }

    // ── redirect safety ───────────────────────────────────────────────────────

    /**
     * @param string $hostile a return_path that would leave this origin
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provideHostileReturnPaths')]
    public function testReturnPathCannotLeaveThisOrigin(string $hostile): void
    {
        $location = $this->post(['form_key' => 'k', 'email' => 'a@example.test', 'return_path' => $hostile])
            ->getHeaderLine('Location');

        self::assertSame('/?contact=ok', $location, 'Hostile return_path must fall back, never redirect off-site.');
    }

    /** @return iterable<string, array{string}> */
    public static function provideHostileReturnPaths(): iterable
    {
        yield 'absolute url' => ['https://evil.example/x'];
        yield 'protocol relative' => ['//evil.example/x'];
        yield 'backslash authority' => ['/\\evil.example/x'];
        yield 'scheme only' => ['javascript:alert(1)'];
        yield 'header injection' => ["/ok\r\nX-Injected: 1"];
        yield 'empty' => [''];
    }

    // ── harness ───────────────────────────────────────────────────────────────

    /** @param array<string, mixed> $body */
    private function post(array $body): ResponseInterface
    {
        return (new SubmitContactFormHandlerFactory($this))->build()->handle($this->request($body));
    }

    /** @param array<string, mixed> $body */
    public function request(
        array $body,
        ?string $origin = 'https://' . self::HOST,
        ?string $referer = null,
        string $ip = '198.51.100.7',
    ): ServerRequestInterface {
        $request = $this->factory
            ->createServerRequest('POST', 'https://' . self::HOST . ContactSubmissionProxyRoute::PATH)
            ->withParsedBody($body)
            ->withHeader('X-Forwarded-For', $ip);

        if ($origin !== null) {
            $request = $request->withHeader('Origin', $origin);
        }

        if ($referer !== null) {
            $request = $request->withHeader('Referer', $referer);
        }

        return $request;
    }

    public function factory(): Psr17Factory
    {
        return $this->factory;
    }

    public function sender(): RecordingSender
    {
        return $this->sender;
    }

    public function rateLimit(): InMemoryRateLimitStorage
    {
        return $this->rateLimit;
    }

    public function logger(): RecordingLogger
    {
        return $this->logger;
    }
}

/** Builds the handler with the test doubles, so each test can vary one knob. */
final readonly class SubmitContactFormHandlerFactory
{
    public function __construct(private SubmitContactFormHandlerTest $test)
    {
    }

    public function build(
        int $perIpLimit = 10,
        int $perFormLimit = 300,
        ?ContactFormSchema $schema = null,
        bool $withoutSchema = false,
    ): SubmitContactFormHandler {
        $resolved = $withoutSchema ? null : ($schema ?? new ContactFormSchema('k', [
            new ContactFormField('email', 'Email', 'email', true),
        ]));

        $factory = $this->test->factory();

        return new SubmitContactFormHandler(
            new FixedSchemaProvider($resolved),
            $this->test->sender(),
            $this->test->rateLimit(),
            new ProblemDetailsResponseFactory($factory, $factory),
            $factory,
            new FixedClock(),
            $this->test->logger(),
            $perIpLimit,
            $perFormLimit,
        );
    }
}

final readonly class FixedSchemaProvider implements ContactFormSchemaProviderInterface
{
    public function __construct(private ?ContactFormSchema $schema)
    {
    }

    public function schemaFor(string $formKey): ?ContactFormSchema
    {
        return $this->schema;
    }
}

final class RecordingSender implements ContactSubmissionSenderInterface
{
    /** @var list<array{formKey: string, values: array<string, string>, consent: bool}> */
    public array $sent = [];

    public ContactSubmissionSendResult $result;

    public function __construct()
    {
        $this->result = ContactSubmissionSendResult::delivered();
    }

    public function send(string $formKey, array $values, bool $consent): ContactSubmissionSendResult
    {
        $this->sent[] = ['formKey' => $formKey, 'values' => $values, 'consent' => $consent];

        return $this->result;
    }
}

final class InMemoryRateLimitStorage implements RateLimitStorageInterface
{
    /** @var array<string, int> */
    private array $counts = [];

    public function hit(string $key, int $windowSeconds): array
    {
        $this->counts[$key] = ($this->counts[$key] ?? 0) + 1;

        return ['count' => $this->counts[$key], 'reset_at' => 1_780_000_000];
    }
}

final class RecordingLogger extends AbstractLogger
{
    /** @var list<array{level: string, message: string}> */
    public array $records = [];

    /**
     * @param mixed             $level
     * @param string|\Stringable $message
     * @param array<mixed>      $context
     */
    public function log($level, $message, array $context = []): void
    {
        $this->records[] = ['level' => (string) $level, 'message' => (string) $message];
    }

    public function has(string $level, string $needle): bool
    {
        foreach ($this->records as $record) {
            if ($record['level'] === $level && str_contains($record['message'], $needle)) {
                return true;
            }
        }

        return false;
    }
}
