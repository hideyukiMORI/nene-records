<?php

declare(strict_types=1);

namespace NeNeRecords\Config;

/**
 * Encrypts configuration secrets that must survive at rest in the database.
 *
 * Used for values records is *given* by another product and must present later — the
 * connect-token of records-embed 案1 is the first (#1029). Not for passwords (those are
 * hashed, never decrypted) and not for tokens records itself mints (records mints none).
 */
interface ConfigCipherInterface
{
    /**
     * @param non-empty-string $plaintext
     *
     * @return non-empty-string an opaque envelope safe to store as text
     *
     * @throws ConfigKeyException when no usable key is configured
     */
    public function encrypt(string $plaintext): string;

    /**
     * @param non-empty-string $envelope a value previously returned by {@see encrypt()}
     *
     * @return non-empty-string
     *
     * @throws ConfigKeyException     when no usable key is configured
     * @throws ConfigDecryptException when the envelope is malformed, truncated, or was
     *                                produced under a different key
     */
    public function decrypt(string $envelope): string;
}
