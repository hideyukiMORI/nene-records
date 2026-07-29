<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Config;

use NeNeRecords\Config\AesGcmConfigCipher;
use NeNeRecords\Config\ConfigDecryptException;
use NeNeRecords\Config\ConfigKeyException;
use NeNeRecords\Config\ConfigKeyResolverInterface;
use PHPUnit\Framework\TestCase;

final class AesGcmConfigCipherTest extends TestCase
{
    public function testRoundTripsAValue(): void
    {
        $cipher = new AesGcmConfigCipher($this->keyResolver());

        $envelope = $cipher->encrypt('connect-token-value');

        self::assertSame('connect-token-value', $cipher->decrypt($envelope));
    }

    public function testEnvelopeNeverContainsThePlaintext(): void
    {
        $cipher = new AesGcmConfigCipher($this->keyResolver());

        $envelope = $cipher->encrypt('super-secret-token');

        self::assertStringNotContainsString('super-secret-token', $envelope);
        self::assertStringNotContainsString('super-secret-token', (string) base64_decode($envelope, true));
    }

    public function testSameValueEncryptsDifferentlyEachTime(): void
    {
        $cipher = new AesGcmConfigCipher($this->keyResolver());

        // A per-call random IV is what stops "these two orgs share a token" from being
        // readable off the ciphertext column alone.
        self::assertNotSame($cipher->encrypt('same'), $cipher->encrypt('same'));
    }

    public function testTamperedEnvelopeIsRejected(): void
    {
        $cipher = new AesGcmConfigCipher($this->keyResolver());
        $raw = (string) base64_decode($cipher->encrypt('connect-token-value'), true);

        // Flip a bit in the ciphertext body; GCM's tag must catch it.
        $raw[strlen($raw) - 1] = $raw[strlen($raw) - 1] === 'a' ? 'b' : 'a';

        $tampered = base64_encode($raw);

        if ($tampered === '') {
            self::fail('Re-encoding the tampered envelope produced an empty string.');
        }

        $this->expectException(ConfigDecryptException::class);
        $cipher->decrypt($tampered);
    }

    public function testEnvelopeFromAnotherKeyIsRejected(): void
    {
        $envelope = (new AesGcmConfigCipher($this->keyResolver()))->encrypt('connect-token-value');
        $rotated = new AesGcmConfigCipher($this->keyResolver(str_repeat("\x02", 32)));

        // Rotating the key makes stored values unreadable rather than silently wrong —
        // the operator re-pastes the token (#1029: no re-encryption path by design).
        $this->expectException(ConfigDecryptException::class);
        $rotated->decrypt($envelope);
    }

    public function testMalformedEnvelopeIsRejected(): void
    {
        $cipher = new AesGcmConfigCipher($this->keyResolver());

        $this->expectException(ConfigDecryptException::class);
        $cipher->decrypt('not-base64-at-all!!');
    }

    public function testMissingKeyFailsClosedOnEncrypt(): void
    {
        $cipher = new AesGcmConfigCipher(new class () implements ConfigKeyResolverInterface {
            public function resolve(): string
            {
                throw new ConfigKeyException('no key');
            }
        });

        // Fail-closed: without a key records must refuse to *store* a secret, not fall back
        // to plaintext.
        $this->expectException(ConfigKeyException::class);
        $cipher->encrypt('connect-token-value');
    }

    private function keyResolver(?string $key = null): ConfigKeyResolverInterface
    {
        $resolved = $key ?? str_repeat("\x01", 32);

        return new class ($resolved) implements ConfigKeyResolverInterface {
            public function __construct(private string $key)
            {
            }

            public function resolve(): string
            {
                /** @var non-empty-string */
                return $this->key;
            }
        };
    }
}
