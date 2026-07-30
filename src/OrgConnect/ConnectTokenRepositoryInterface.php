<?php

declare(strict_types=1);

namespace NeNeRecords\OrgConnect;

/**
 * Storage for the connect-tokens of the current organization.
 *
 * Deliberately split into a summary read (no secret material) and a single envelope read,
 * so a caller that only wants to display state cannot accidentally hold the ciphertext.
 * Scoping to the organization is ambient, as everywhere else in this codebase.
 */
interface ConnectTokenRepositoryInterface
{
    /** @return list<ConnectTokenSummary> */
    public function findAllSummaries(): array;

    public function findSummary(ConnectTokenService $service): ?ConnectTokenSummary;

    /**
     * The stored envelope, still encrypted. Decrypting is {@see ConnectTokenProviderInterface}'s job.
     */
    public function findEnvelope(ConnectTokenService $service): ?string;

    /**
     * Installs or replaces the token for one service and returns the resulting state.
     *
     * @param non-empty-string $envelope
     */
    public function save(ConnectTokenService $service, string $envelope, string $hint): ConnectTokenSummary;

    public function delete(ConnectTokenService $service): void;
}
