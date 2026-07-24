<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

/**
 * Computes the privacy-preserving visitor hash (ADR 0006 D2).
 *
 *   visitor_hash = lowercase_hex( sha256( daily_salt || client_ip || ':' || org_id ) )
 *
 * The raw IP is only ever used here to derive the hash and is never persisted. The salt
 * rotates per calendar day (see {@see AnalyticsSaltRepositoryInterface}), and the org id
 * is mixed in so the same visitor is not correlatable across tenants. Because the salt is
 * discarded after the retention window, past days cannot be re-identified from known IPs.
 *
 * The hub-owned LP beacon (#1008) mirrors this exact recipe against the same salt store so
 * unique counts reconcile between SSR (Records) and LP hits.
 */
final class VisitorHasher
{
    public static function hash(string $dailySalt, string $clientIp, int $orgId): string
    {
        return hash('sha256', $dailySalt . $clientIp . ':' . (string) $orgId);
    }
}
