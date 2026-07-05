<?php

declare(strict_types=1);

namespace NeNeRecords\Database\Preflight;

/**
 * NeNe Records' stable database-lineage identity for machine database preflight (#648).
 *
 * Stamped into the application's own database (`nene2_app_identity`, by the
 * `StampAppIdentityMarker` migration) and compared read-only against a candidate by
 * the framework's `DefaultDatabaseCandidateInspector`, so `/machine/database/preflight`
 * can refuse a database that belongs to a *different* application even when it carries
 * the same NENE2 migration ledger.
 *
 * The identity's `tenantId` is intentionally null: NeNe Records is a shared-database,
 * row-level (`org_id`) multi-tenant application, so the database is not partitioned per
 * tenant and database-level tenant matching is `not_applicable`. Per-candidate caution
 * for multi-tenant deployments is expressed via `CandidateProfile::$multiTenant` instead.
 */
final class PreflightIdentity
{
    /** Stable identifier of this application's database lineage (marker `application_id`). */
    public const APPLICATION_ID = 'nene-records';
}
