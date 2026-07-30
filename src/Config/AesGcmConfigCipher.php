<?php

declare(strict_types=1);

namespace NeNeRecords\Config;

use SensitiveParameter;

/**
 * AES-256-GCM implementation of {@see ConfigCipherInterface}.
 *
 * Envelope layout, base64-encoded as one string: `iv(12) || tag(16) || ciphertext`.
 * GCM is chosen over CBC so a tampered envelope fails to open instead of decrypting into
 * attacker-influenced plaintext; the tag is what makes `decrypt()` able to reject a value
 * that was edited in the database.
 *
 * The key never appears in the envelope, so rotating `NENE_RECORDS_CONFIG_KEY` does not
 * corrupt stored rows — it makes them unreadable, which surfaces as {@see ConfigDecryptException}
 * and is resolved by re-pasting the token (contact can re-issue it).
 */
final readonly class AesGcmConfigCipher implements ConfigCipherInterface
{
    private const CIPHER = 'aes-256-gcm';
    private const IV_LENGTH = 12;
    private const TAG_LENGTH = 16;

    public function __construct(
        private ConfigKeyResolverInterface $keys,
    ) {
    }

    public function encrypt(#[SensitiveParameter] string $plaintext): string
    {
        $key = $this->keys->resolve();
        $iv = random_bytes(self::IV_LENGTH);
        $tag = '';

        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag, '', self::TAG_LENGTH);

        if ($ciphertext === false) {
            throw new ConfigKeyException('Failed to encrypt configuration value.');
        }

        return base64_encode($iv . $tag . $ciphertext);
    }

    public function decrypt(#[SensitiveParameter] string $envelope): string
    {
        $key = $this->keys->resolve();
        $raw = base64_decode($envelope, true);

        if ($raw === false || strlen($raw) <= self::IV_LENGTH + self::TAG_LENGTH) {
            throw new ConfigDecryptException('Configuration envelope is malformed or truncated.');
        }

        $iv = substr($raw, 0, self::IV_LENGTH);
        $tag = substr($raw, self::IV_LENGTH, self::TAG_LENGTH);
        $ciphertext = substr($raw, self::IV_LENGTH + self::TAG_LENGTH);

        $plaintext = openssl_decrypt($ciphertext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);

        // An empty result is treated as failure, not as a valid empty secret: nothing that
        // belongs in here is legitimately empty, and returning '' would hand a caller
        // something that looks usable. The interface promises a non-empty-string.
        if ($plaintext === false || $plaintext === '') {
            throw new ConfigDecryptException('Configuration envelope could not be opened with the configured key.');
        }

        return $plaintext;
    }
}
