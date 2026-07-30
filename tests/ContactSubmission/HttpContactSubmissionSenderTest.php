<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\ContactSubmission;

use NeNeRecords\Config\ConfigDecryptException;
use NeNeRecords\ContactSubmission\ContactIngestPayload;
use NeNeRecords\ContactSubmission\HttpContactSubmissionSender;
use NeNeRecords\OrgConnect\ConnectTokenProviderInterface;
use NeNeRecords\OrgConnect\ConnectTokenService;
use PHPUnit\Framework\TestCase;

final class HttpContactSubmissionSenderTest extends TestCase
{
    private const TOKEN = 'eyJhbGciOiJIUzI1NiJ9.payload.signature-abcd';

    // ── the cross-repo contract ───────────────────────────────────────────────

    /**
     * contact ruling 2026-07-30 (option B): the form is addressed by its public key, and
     * sending both identifiers at once is a 422 upstream *by design*. A bug that added the
     * second key would therefore surface in production, not here — so it is pinned here.
     */
    public function testPayloadAddressesTheFormByPublicKeyOnly(): void
    {
        $payload = ContactIngestPayload::build('ayane-contact', ['email' => 'a@example.test'], true);

        self::assertSame('ayane-contact', $payload['public_form_key']);
        self::assertArrayNotHasKey('contact_form_id', $payload);
    }

    public function testPayloadDeclaresContactsSourceVocabulary(): void
    {
        // Not "records": a released `source` value cannot be renamed, so it must not name the
        // caller of the day.
        self::assertSame('first_party', ContactIngestPayload::build('k', [], false)['source']);
    }

    public function testPayloadCarriesTheFieldValuesAndConsentUnchanged(): void
    {
        $payload = ContactIngestPayload::build('k', ['email' => 'a@example.test', 'message' => 'hi'], true);

        self::assertSame(['email' => 'a@example.test', 'message' => 'hi'], $payload['field_values']);
        self::assertTrue($payload['consent']);
    }

    // ── failure modes: all visible, none silent ───────────────────────────────

    public function testUnconfiguredBaseUrlFailsInsteadOfPretendingToDeliver(): void
    {
        $sender = new HttpContactSubmissionSender(null, $this->tokenProvider(self::TOKEN));

        $result = $sender->send('k', ['email' => 'a@example.test'], false);

        self::assertFalse($result->delivered);
        self::assertStringContainsString('NENE_RECORDS_CONTACT_BASE_URL', (string) $result->reason);
    }

    public function testMissingTokenFailsWithItsOwnReason(): void
    {
        $sender = new HttpContactSubmissionSender('https://contact.example', $this->tokenProvider(null));

        $result = $sender->send('k', ['email' => 'a@example.test'], false);

        self::assertFalse($result->delivered);
        self::assertStringContainsString('No connect token', (string) $result->reason);
    }

    public function testRotatedKeyIsReportedSeparatelyFromAMissingToken(): void
    {
        // "installed but unreadable" and "not installed" need different operator actions
        // (re-paste vs. install), so #1029 keeps them apart and so does this.
        $sender = new HttpContactSubmissionSender('https://contact.example', new class () implements ConnectTokenProviderInterface {
            public function secretFor(ConnectTokenService $service): ?string
            {
                throw new ConfigDecryptException('key rotated');
            }
        });

        $result = $sender->send('k', ['email' => 'a@example.test'], false);

        self::assertFalse($result->delivered);
        self::assertStringContainsString('decrypted', (string) $result->reason);
    }

    public function testFailureReasonNeverCarriesTheToken(): void
    {
        // The reason goes to the operator log; the token must not ride along into it.
        foreach ([null, 'https://127.0.0.1'] as $baseUrl) {
            $sender = new HttpContactSubmissionSender($baseUrl, $this->tokenProvider(self::TOKEN));
            $result = $sender->send('k', ['email' => 'a@example.test'], false);

            self::assertFalse($result->delivered);
            self::assertStringNotContainsString(self::TOKEN, (string) $result->reason);
        }
    }

    public function testUpstreamOnANonPublicAddressIsRefusedBeforeAnyRequest(): void
    {
        // A misconfigured base URL must not turn a public page into a way to reach internal
        // addresses; the guard runs before the connection.
        $sender = new HttpContactSubmissionSender('http://169.254.169.254', $this->tokenProvider(self::TOKEN));

        $result = $sender->send('k', ['email' => 'a@example.test'], false);

        self::assertFalse($result->delivered);
        self::assertStringContainsString('rejected', (string) $result->reason);
    }

    /** @param non-empty-string|null $token */
    private function tokenProvider(?string $token): ConnectTokenProviderInterface
    {
        return new class ($token) implements ConnectTokenProviderInterface {
            /** @param non-empty-string|null $token */
            public function __construct(private readonly ?string $token)
            {
            }

            public function secretFor(ConnectTokenService $service): ?string
            {
                return $this->token;
            }
        };
    }
}
